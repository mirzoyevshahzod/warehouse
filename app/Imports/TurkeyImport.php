<?php

namespace App\Imports;

use App\Models\Turkey;
use App\Models\Auto;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TurkeyImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $carNumber = $row['avtomobil_raqami'] ?? null;

        if (empty($carNumber)) {
            return null; // skip
        }

        $companyName = null;

        $auto = Auto::where('state_number', $carNumber)->first();
        if ($auto) {
            $companyName = $auto->company_name;
        }

        $date = $this->parseDate($row['sana'] ?? null);
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
        return Turkey::updateOrCreate(
            [
                'car_number' => $carNumber,
                'date' => $date,
            ],
            [
                'ordinal_number' => $row['tartib_raqami'] ?? null,
                'input_sequence_number' => $row['kirish_tartib_raqami'] ?? null,
                'entrance' => $row['kirish_joyi'] ?? null,
                'company_name' => $companyName,
                'phone' => $phone,
            ]
        );
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;

        try {
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
