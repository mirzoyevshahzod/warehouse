<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class DriversImport implements OnEachRow, WithHeadingRow
{
    public function onRow(Row $row)
    {
        
        $row = $row->toArray();

        if (empty($row['mashina_raqami'])) {
            return;
        }

        Driver::updateOrCreate(
            [
                'number' => $row['mashina_raqami'],
            ],
            [
                'full_name' => $row['ism_familya'] ?? null,
                'phone' => $this->formatPhone($row['telefon_raqam'] ?? null),
                'length' => $this->normalize($row['uzunligi'] ?? null),
                'width' => $this->normalize($row['kengligi'] ?? null),
                'height' => $this->normalize($row['balandligi'] ?? null),
                'trailer_number' => $row['treyler_raqami'] ?? null,
                'capacity' => $this->normalize($row['sigimi'] ?? null),
                'lifting' => $this->normalize($row['yuk_kotarish'] ?? null),
                'brand' => $row['brandi'] ?? null,
            ]
        );
    }


    private function formatPhone($phone)
    {
        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);

        if (strlen($phone) === 9) {
            return '998' . $phone;
        }

        return $phone;
    }

    private function normalize($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^\d.]+/', '', $value);
            return is_numeric($normalized) ? $normalized : null;
        }

        return null;
    }
}