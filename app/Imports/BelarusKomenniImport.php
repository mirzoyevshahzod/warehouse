<?php

namespace App\Imports;

use App\Models\Auto;
use App\Models\BelarusKomenni;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BelarusKomenniImport implements ToModel, WithHeadingRow
{
    protected $autos;

    public function __construct()
    {
        $normalize = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper($v));

        // 🔥 barcha autosni oldindan yuklaymiz (TEZ)
        $this->autos = Auto::all()->keyBy(fn($a) => $normalize($a->state_number));
    }

    public function model(array $row)
    {
        $normalize = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper($v));

        $carNumberRaw = $row['reg_nomer'] ?? null;
        $carNumber = $normalize($carNumberRaw);

        // 🔥 company name olish
        $companyName = null;

        if ($carNumber) {
            $autoData = $this->autos[$carNumber] ?? null;

            if ($autoData) {
                $companyName = $autoData->company_name;
            }
        }

        $phone = null;
        if (!empty($row['phones'])) {
            $phone = preg_replace('/[^0-9]/', '', (string)$row['phones']);
        }

        return BelarusKomenni::updateOrCreate(

            // 🔥 UNIQUE (duplicate bo‘lmasin)
            [
                'car_number' => $carNumber,
                'date_of_registration_in_the_zo' =>
                    $this->parseDateTime($row['data_registracii_v_zo'] ?? null),
            ],

            // 🔥 UPDATE
            [
                'call_order' => $row['poriadok_vyzova'] ?? null,
                'queue_type' => $row['tip_oceredi'] ?? null,
                'status_changed' =>
                    $this->parseDateTime($row['status_izmenen'] ?? null),
                'status' => $row['status'] ?? null,
                'company_name' => $companyName,
                'phone' => $phone,
            ]
        );
    }

    private function parseDateTime($value)
    {
        if (empty($value)) return null;

        try {
            // Excel numeric date bo‘lsa
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }

            // string bo‘lsa
            return \Carbon\Carbon::parse($value)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }
}