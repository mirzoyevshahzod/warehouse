<?php

namespace App\Imports;

use App\Models\UzbekistanExport;
use App\Models\UzbekistanExporterContact;

/**
 * "Download_UZBEKISTAN_Export_Trade_Data_for_the_Period_from_YYYY_MM.xlsx" faylini yuklaydi.
 *
 * Faylda deklaratsiya va tovar raqami yo'q, shuning uchun har qator uchun kalit
 * qiymatlaridan hisoblanadi: sha1(barcha ustunlar) + shu qatorning fayl ichida nechanchi
 * marta uchrashi. Natijada bir faylni qayta yuklash dublikat yaratmaydi, faylning o'zida
 * bir xil bo'lgan qatorlar esa (alohida tovar pozitsiyalari) yo'qolmaydi.
 *
 * Faylda mas'ul xodim yo'q — firmaga (STIR) qo'lda kiritilgan xodim bo'lsa, u qo'yiladi.
 */
class UzbekistanExportImport extends TradeFileReader
{
    protected const EXPECTED_HEADERS = [
        3 => 'Exporter ID',
        4 => 'HS code',
        13 => 'Destination country',
    ];

    protected const UNIQUE_BY = ['row_key'];

    /** Excel ustun indekslari (0 dan) */
    private const COL = [
        'date' => 0,
        'importer_name' => 1,
        'exporter_name' => 2,
        'exporter_inn' => 3,
        'hs_code' => 4,
        'product_description' => 5,
        'quantity' => 6,
        'quantity_unit' => 7,
        'net_weight_kg' => 8,
        'invoice_amount_usd' => 9,
        'invoice_unit_price_usd' => 10,
        'customs_value_usd' => 11,
        'statistical_value_usd' => 12,
        'destination_country' => 13,
        'destination' => 14,
        'transit_country' => 15,
        'customs_system' => 16,
        'supervision_method' => 17,
        'transaction_method' => 18,
        'transportation_method' => 19,
        'regime_type' => 20,
        'regime_code' => 21,
        'declaration_type_name' => 22,
        'previous_declaration_type' => 23,
        'freight_transport_type_code' => 24,
        'previous_declaration_type_name' => 25,
        'delivery_condition_code' => 26,
        'delivery_condition_name' => 27,
        'transportation_method_code' => 28,
    ];

    private const NUMBERS = [
        'quantity', 'net_weight_kg',
        'invoice_amount_usd', 'invoice_unit_price_usd', 'customs_value_usd', 'statistical_value_usd',
    ];

    /** @var array<string, int> qator hash'i => fayl ichida necha marta uchradi */
    private array $seen = [];

    /** @var array<string, array> STIR => qo'lda kiritilgan xodim ustunlari */
    private array $contacts;

    public function __construct()
    {
        $this->contacts = UzbekistanExporterContact::all()
            ->mapWithKeys(fn ($c) => [$c->exporter_inn => $c->toExportColumns()])
            ->all();
    }

    protected function modelClass(): string
    {
        return UzbekistanExport::class;
    }

    protected function mapRow(array $row): ?array
    {
        $r = [];
        foreach (self::COL as $field => $index) {
            $r[$field] = $this->text($row[$index] ?? null);
        }

        // bo'sh qator yoki fayl oxiridagi qoldiq
        if ($r['date'] === null && $r['hs_code'] === null && $r['product_description'] === null) {
            return null;
        }

        // kalit — qayta ishlashdan oldingi (matn ko'rinishidagi) qiymatlardan
        $hash = sha1(json_encode(array_values($r), JSON_UNESCAPED_UNICODE));
        $occurrence = $this->seen[$hash] = ($this->seen[$hash] ?? 0) + 1;
        $r['row_key'] = $hash.'-'.$occurrence;

        $r['date'] = $this->date($row[self::COL['date']] ?? null);
        foreach (self::NUMBERS as $field) {
            $r[$field] = $this->number($row[self::COL[$field]] ?? null);
        }

        // kalitga kirmaydi — xodim keyin o'zgarsa ham qator o'sha qator bo'lib qoladi
        $r += $this->contacts[$r['exporter_inn']] ?? UzbekistanExporterContact::emptyExportColumns();

        return $r;
    }
}
