<?php

namespace App\Imports;

use App\Models\Qozoq;
use App\Models\Auto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Row;

class QozoqImport implements 
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

        Qozoq::updateOrCreate(
            [
                'boundary_name' => $row['chegara_nomi'] ?? null,
                'car_number' => $carNumber,
                'date_and_time' => $date,
            ],
            [
                'status' => $row['status'] ?? null,
                'company_name' => $companyName,
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
            // 05.05.2026 00:00-01:00
            [$date, $timeRange] = explode(' ', $value);

            // 00:00 ni olish
            [$startTime] = explode('-', $timeRange);

            return Carbon::createFromFormat('d.m.Y H:i', "$date $startTime")
                ->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
