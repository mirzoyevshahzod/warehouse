<?php

namespace App\Imports;

use App\Models\CheckedQozoq;
use App\Models\Auto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Row;

class CheckedQozoqImport implements 
    OnEachRow,
    WithHeadingRow,
    WithBatchInserts
{
    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $carNumber = isset($row['mashina_raqami'])
            ? strtoupper(trim($row['mashina_raqami']))
            : null;

        $companyName = null;

        if ($carNumber) {
            $auto = Auto::whereRaw('UPPER(TRIM(state_number)) = ?', [$carNumber])->first();
            $companyName = $auto?->company_name;
        }

        $date = $this->parseDateTime($row['sana_va_vaqt'] ?? null);
         $phone = null;

        if (!empty($row['telefon'])) {

            $raw = $row['telefon'];

            // agar float bo‘lsa (scientific format)
            if (is_numeric($raw)) {
                $phone = number_format($raw, 0, '', '');
            } else {
                // string bo‘lsa
                $phone = preg_replace('/[^0-9]/', '', $raw);
            }
        }
        CheckedQozoq::updateOrCreate(
        [
            'car_number' => $carNumber,
        ],
        [
            'boundary_name' => $row['chegara_nomi'] ?? null,
            'date_and_time' => $date,
            'status' => $row['status'] ?? null,
            'company_name' => $companyName,
            'phone' => $phone,
        ]
    );
    }

    public function batchSize(): int
    {
        return 1000;
    }

    private function parseDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // avtomatik aniqlaydi (ENG TO‘G‘RI)
            return Carbon::parse($value)->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return null;
        }
    }
}
