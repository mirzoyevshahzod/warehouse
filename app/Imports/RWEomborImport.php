<?php

namespace App\Imports;

use App\Models\EOmbor;
use App\Models\RWEOmbor;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RWEomborImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['document_number'])) {
            return null; // skip
        }

        return RWEOmbor::updateOrCreate(
            [
                'document_number' => $row['document_number'],
            ],
            [
                'custom_code'      => $row['custom_code'] ?? null,
                'custom_date'      => $this->parseDate($row['custom_date'] ?? null),
                'TEBHN_number'     => $row['tebhn_number'] ?? null,
                'transport_number' => $row['transport_number'] ?? null,
                'gross_weight'     => $row['gross_weight'] ?? null,
                'inn'              => $row['inn'] ?? null,
                'recipient_name'   => $row['recipient_name'] ?? null,
                'delivery_post'    => $row['delivery_post'] ?? null,
                'delivery_date'    => $this->parseDate($row['delivery_date'] ?? null),
                'arrival_place'    => $row['arrival_place'] ?? null,
                'status'           => $row['status'] ?? null,
            ]
        );
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }

            foreach (['d/m/y', 'd/m/Y', 'd.m.Y'] as $format) {
                try {
                    return \Carbon\Carbon::createFromFormat($format, $value)
                        ->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            return \Carbon\Carbon::parse($value)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }
}

