<?php

namespace App\Imports;

use App\Models\Auto;
use App\Models\BelarusKozlovichi;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BelarusKozlovichiImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

        $carNumber = $row['regnomer'] ?? null;

        $companyName = null;

        if ($carNumber) {
            $auto = Auto::where('state_number', $carNumber)->first();
            if ($auto) {
                $companyName = $auto->company_name;
            }
        }
        return new BelarusKozlovichi([
            'call_order' => $row['poriadok_vyzova'] ?? null,
            'queue_type' => $row['tip_oceredi'] ?? null,
            'car_number' => $row['regnomer'] ?? null,

            'date_of_registration_in_the_zo' =>
                $this->parseDateTime($row['data_registracii_v_zo'] ?? null),

            'status_changed' =>
                $this->parseDateTime($row['status_izmenen'] ?? null),

            'status' => $row['status'] ?? null,
            'company_name' => $companyName,
        ]);
    }

    private function parseDateTime($value)
    {
        if (empty($value)) return null;

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s d.m.Y', $value)
                ->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
