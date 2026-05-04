<?php

namespace App\Imports;

use App\Models\ImportQozoq;
use App\Models\Auto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Row;

class ImportQozoqImport implements 
    OnEachRow,
    WithHeadingRow,
    WithBatchInserts
{
    public function onRow(Row $row)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
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
        
       ImportQozoq::updateOrCreate(
        [
            'car_number' => $carNumber,
            'date_and_time' => $date,
        ],
        [
            'boundary_name' => $row['chegara_nomi'] ?? null,
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
        // 1️⃣ format: 10.04.2026 00:00-01:00
        if (str_contains($value, '.')) {

            [$date, $timeRange] = explode(' ', $value);
            [$startTime] = explode('-', $timeRange);

            return Carbon::createFromFormat('d.m.Y H:i', "$date $startTime")
                ->format('Y-m-d H:i:s');
        }

        // 2️⃣ format: 2026-04-15 08:00:00
        return Carbon::parse($value)->format('Y-m-d H:i:s');

    } catch (\Exception $e) {
        return null;
    }
}
}
