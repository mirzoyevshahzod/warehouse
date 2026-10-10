<?php

namespace App\Imports;

use App\Models\UzbekistanImport;
use App\Support\AddressParser;
use App\Support\ContactParser;
use Carbon\Carbon;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use ZipArchive;

/**
 * "Download_UZBEKISTAN_Import_Trade_Data_for_the_Period_from_YYYY_MM.xlsx" faylini yuklaydi.
 *
 * 1-qator — sarlavha, 2-qator — ustun nomlari, 3-qatordan ma'lumot.
 * Ustun nomlarida dublikat bor ("Address of the applicant" ikki marta),
 * shuning uchun ustunlar nomi bo'yicha emas, indeksi bo'yicha o'qiladi.
 *
 * Katta fayllar uchun OpenSpout bilan qatorma-qator (stream) o'qiladi —
 * fayl to'liq xotiraga yuklanmaydi. Queue job ichidan chaqiriladi.
 */
class UzbekistanImportImport
{
    private const BATCH_SIZE = 500;

    /** Ustun nomlari qatorini tanish va noto'g'ri fayl yuklanmasligi uchun */
    private const EXPECTED_HEADERS = [
        4 => 'declaration number',
        20 => 'HS Code',
        71 => 'Customs Office',
    ];

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

    private const UNIQUE_BY = ['customs_code', 'registration_date', 'declaration_number', 'item_no'];

    /** @var array<string, array> manzil/kontakt parse natijalari keshi (bir xil qiymatlar ko'p takrorlanadi) */
    private array $cache = [];

    /**
     * Faylni o'qib bazaga yozadi.
     *
     * @param  callable(int):void|null  $onProgress  har bir batch'dan keyin yozilgan qatorlar soni bilan chaqiriladi
     * @return int yozilgan (qo'shilgan yoki yangilangan) qatorlar soni
     */
    public function import(string $path, ?callable $onProgress = null): int
    {
        $reader = new Reader();
        $reader->open($path);

        $processed = 0;
        $batch = [];
        $headerFound = false;
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    $rowNumber++;

                    if (! $headerFound) {
                        $headerFound = $this->isHeaderRow($values);
                        if (! $headerFound && $rowNumber >= 10) {
                            throw new RuntimeException(
                                "Fayl formati noto'g'ri: ustun nomlari qatori topilmadi (kutilgan: \"declaration number\", \"HS Code\", \"Customs Office\")"
                            );
                        }
                        continue;
                    }

                    $record = $this->mapRow($values);
                    if ($record === null) {
                        continue;
                    }

                    // bitta batch ichida takror bo'lsa — oxirgisi qoladi
                    $batch[implode('|', array_map(fn ($k) => $record[$k], self::UNIQUE_BY))] = $record;

                    if (count($batch) >= self::BATCH_SIZE) {
                        $processed += $this->save($batch);
                        $batch = [];
                        $onProgress && $onProgress($processed);
                    }
                }

                // faqat birinchi varaq
                break;
            }

            if ($batch) {
                $processed += $this->save($batch);
                $onProgress && $onProgress($processed);
            }
        } finally {
            $reader->close();
        }

        if (! $headerFound) {
            throw new RuntimeException("Fayl formati noto'g'ri yoki fayl bo'sh");
        }

        return $processed;
    }

    /**
     * Progress uchun ma'lumot qatorlari soni: varaq XML'idagi "<row " teglari sanaladi
     * (kataklar parse qilinmaydi, shuning uchun tez). Sarlavha va ustun nomlari qatori ayiriladi.
     */
    public static function estimateRows(string $path): ?int
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $stream = $zip->getStream('xl/worksheets/sheet1.xml');
        if (! $stream) {
            $zip->close();

            return null;
        }

        $count = 0;
        $tail = '';
        while (! feof($stream)) {
            $chunk = $tail.fread($stream, 1048576);
            $count += substr_count($chunk, '<row ');
            // bo'lak chegarasida kesilgan tegni keyingi bo'lakda sanash uchun
            $tail = substr($chunk, -4);
            $count -= substr_count($tail, '<row ');
        }

        fclose($stream);
        $zip->close();

        return max(0, $count - 2);
    }

    private function isHeaderRow(array $values): bool
    {
        foreach (self::EXPECTED_HEADERS as $index => $name) {
            if (strcasecmp(trim((string) ($values[$index] ?? '')), $name) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function save(array $records): int
    {
        $now = now();
        $records = array_values(array_map(
            fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now],
            $records
        ));

        $update = array_values(array_diff(array_keys($records[0]), [...self::UNIQUE_BY, 'created_at']));

        UzbekistanImport::upsert($records, self::UNIQUE_BY, $update);

        return count($records);
    }

    private function mapRow(array $row): ?array
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

    private function text($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim(preg_replace('/[ \t]+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    private function number($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {

            // "2026-08-31T00:00:00" / "2026-08-31"
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
