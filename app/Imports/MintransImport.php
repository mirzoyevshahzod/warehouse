<?php

namespace App\Imports;

use App\Models\Mintrans;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MintransImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Mintrans([
            'model' => $row['rusumi'] ?? null,
            'load_capacity' => $row['yuk_kotarish_qobiliyati'] ?? null,
            'license_number' => $row['litsenziya_varaqasi'] ?? null,
            'state_number' => $row['davlat_raqami'] ?? null,
            'company_name' => $row['korxona_nomi'] ?? null,
            'phone_number' => $row['telefon_raqami'] ?? null,
            'type_of_activity' => $row['faoliyat_turi'] ?? null,
            'transport_type' => $row['transport_turi'] ?? null,
            'cargo_type' => $row['yuk_turi'] ?? null,
            'date_given' => $this->parseDate($row['berilgan_sana'] ?? null),
            'validity_period' => $this->parseDate($row['amal_qilish_muddati'] ?? null),
            'status' => $row['holati'] ?? null,
            'territorial_management' => $row['hududiy_boshqarma'] ?? null,
            'inn' => $row['inn'] ?? null,
        ]);
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;

        try {
            return Carbon::createFromFormat('d.m.Y', $value)
                ->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
