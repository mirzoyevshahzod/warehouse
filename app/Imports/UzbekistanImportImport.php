<?php

namespace App\Imports;

use App\Models\UzbekistanImport;
use App\Support\AddressParser;
use App\Support\ContactParser;

/**
 * "Download_UZBEKISTAN_Import_Trade_Data_for_the_Period_from_YYYY_MM.xlsx" faylini yuklaydi.
 *
 * Ustun nomlarida dublikat bor ("Address of the applicant" ikki marta),
 * shuning uchun ustunlar nomi bo'yicha emas, indeksi bo'yicha o'qiladi.
 */
class UzbekistanImportImport extends TradeFileReader
{
    protected const EXPECTED_HEADERS = [
        4 => 'declaration number',
        20 => 'HS Code',
        71 => 'Customs Office',
    ];

    protected const UNIQUE_BY = ['customs_code', 'registration_date', 'declaration_number', 'item_no'];

    /** Excel ustun indekslari (0 dan) */
    private const COL = [
        'date' => 0,
        'registration_date' => 1,
        'regime_type' => 2,
        'regime_code' => 3,
        'declaration_number' => 4,
        'importer_name' => 5,
        'importer_name_2' => 6,
        'importer_address' => 7,
        'importer_inn' => 8,
        'importer_phone' => 9,
        'contract_holder' => 10,
        'contract_holder_code' => 11,
        'contract_holder_address' => 12,
        'exporter_standard_name' => 15,
        'exporter_local_name' => 16,
        'exporter_address' => 17,
        'exporter_code' => 18,
        'exporter_phone' => 19,
        'hs_code' => 20,
        'product_description' => 21,
        'brand' => 22,
        'item_no' => 23,
        'quantity' => 24,
        'quantity_unit' => 25,
        'net_weight_kg' => 26,
        'gross_weight_kg' => 27,
        'invoice_amount_fc' => 29,
        'customs_amount_usd' => 30,
        'customs_amount_fc' => 31,
        'statistical_amount_usd' => 32,
        'total_invoice_amount_fc' => 34,
        'total_customs_amount_fc' => 36,
        'total_tariffs' => 37,
        'contract_currency' => 38,
        'usd_exchange_rate' => 39,
        'fc_exchange_rate' => 40,
        'trade_mode' => 41,
        'trade_mode_code' => 42,
        'contract_date' => 43,
        'contract_no' => 44,
        'contract_signing_country' => 45,
        'contract_char_code' => 46,
        'origin_country' => 47,
        'delivery_place' => 48,
        'shipment_country' => 49,
        'trading_country' => 50,
        'transport_country' => 51,
        'transit_country' => 52,
        'destination_country' => 53,
        'declaration_date' => 54,
        'transit_date' => 55,
        'declaring_unit' => 56,
        'declaring_unit_code' => 57,
        'applicant_address' => 58,
        'declarant_name' => 59,
        'applicant_code' => 60,
        'applicant_email' => 61,
        'applicant_phone' => 62,
        'applicant_contract_no' => 63,
        'applicant_city' => 64,
        'transport_type' => 65,
        'overseas_transport_type' => 66,
        'vehicle' => 67,
        'border_vehicle_number' => 68,
        'customs_code' => 69,
        'border_customs' => 70,
        'customs_office' => 71,
    ];

    private const DATES = ['date', 'registration_date', 'declaration_date', 'contract_date', 'transit_date'];

    private const NUMBERS = [
        'quantity', 'net_weight_kg', 'gross_weight_kg',
        'invoice_amount_fc', 'customs_amount_usd', 'customs_amount_fc', 'statistical_amount_usd',
        'total_invoice_amount_fc', 'total_customs_amount_fc', 'total_tariffs',
        'usd_exchange_rate', 'fc_exchange_rate',
    ];

    /** @var array<string, array> manzil/kontakt parse natijalari keshi (bir xil qiymatlar ko'p takrorlanadi) */
    private array $cache = [];

    protected function modelClass(): string
    {
        return UzbekistanImport::class;
    }

    protected function mapRow(array $row): ?array
    {
        $r = [];
        foreach (self::COL as $field => $index) {
            $r[$field] = $this->text($row[$index] ?? null);
        }

        // bo'sh qator yoki fayl oxiridagi qoldiq
        if ($r['declaration_number'] === null) {
            return null;
        }

        foreach (self::DATES as $field) {
            $r[$field] = $this->date($row[self::COL[$field]] ?? null);
        }
        foreach (self::NUMBERS as $field) {
            $r[$field] = $this->number($row[self::COL[$field]] ?? null);
        }
        $r['item_no'] = $r['item_no'] !== null ? (int) $r['item_no'] : null;

        // Manzillar
        $importer = $this->address($r['importer_address'], true);
        $r['importer_region'] = $importer['region'];
        $r['importer_district'] = $importer['district'] ?? $importer['city'];
        $r['importer_village'] = $importer['mahalla'];

        $holder = $this->address($r['contract_holder_address'], true);
        $r['contract_holder_region'] = $holder['region'];
        $r['contract_holder_district'] = $holder['district'] ?? $holder['city'];
        $r['contract_holder_street'] = $holder['street'];

        $exporter = $this->address($r['exporter_address'], false);
        // manzilda davlat yozilmagan bo'lsa — savdo qilingan davlat eksportyor davlati bo'ladi
        $r['exporter_country'] = $exporter['country'] ?? $r['trading_country'];
        $r['exporter_region'] = $exporter['region'];
        $r['exporter_city'] = $exporter['city'];
        $r['exporter_street'] = $exporter['street'];

        $applicant = $this->address($r['applicant_address'], true);
        $r['applicant_region'] = $applicant['region'];
        $r['applicant_district'] = $applicant['district'] ?? $applicant['city'];
        $r['applicant_street'] = $applicant['street'];

        // Transport: "08KG648ARH/06KG933PE" => tyagach + tirkama
        [$vehicle, $trailer] = array_pad(array_map('trim', explode('/', (string) $r['vehicle'], 2)), 2, null);
        unset($r['vehicle']);
        $r['vehicle_number'] = $vehicle !== '' ? $vehicle : null;
        $r['trailer_number'] = $trailer !== '' ? $trailer : null;

        // Bojxona izohi: mas'ul shaxs
        $contact = $this->cache['contact'][$r['customs_office'] ?? ''] ??= ContactParser::parse($r['customs_office']);
        $r['responsible_person_name'] = $contact['full_name'];
        $r['responsible_person_position'] = $contact['position'];
        $r['responsible_person_phone'] = $contact['phone'];

        return $r;
    }

    private function address(?string $value, bool $uzbek): array
    {
        return $this->cache['address'][(int) $uzbek][$value ?? ''] ??= AddressParser::parse($value, $uzbek);
    }
}
