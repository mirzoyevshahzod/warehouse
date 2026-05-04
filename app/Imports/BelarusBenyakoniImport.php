<?php

namespace App\Imports;

use App\Models\BelarusBenyakoni;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Auto;

class BelarusBenyakoniImport implements ToModel, WithHeadingRow
{
    protected $autos;

    public function __construct()
    {
        $normalize = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper($v));

        $this->autos = Auto::all()->keyBy(fn($a) => $normalize($a->state_number));
    }
    public function model(array $row)
    {
        $normalize = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper($v));

        $carNumberRaw = $row['reg_nomer'] ?? null;
        $carNumber = $normalize($carNumberRaw);

        $companyName = null;

        if ($carNumber) {
            $autoData = $this->autos[$carNumber] ?? null;

            if ($autoData) {
                $companyName = $autoData->company_name;
            }
        }

        // telefon
        $phone = null;
        if (!empty($row['phones'])) {
            $phone = preg_replace('/[^0-9]/', '', (string)$row['phones']);
        }

        return BelarusBenyakoni::updateOrCreate(

            // 🔥 UNIQUE QISM
            [
                'car_number' => $carNumberRaw,
                'date_of_registration_in_the_zo' =>
                    $this->parseDateTime($row['data_registracii_v_zo'] ?? null),
            ],

            // 🔥 UPDATE QISM
            [
                'call_order' => $row['poriadok_vyzova'] ?? null,
                'queue_type' => $row['tip_oceredi'] ?? null,
                'status_changed' =>
                    $this->parseDateTime($row['status_izmenen'] ?? null),
                'status' => $row['status'] ?? null,
                'company_name' => $companyName,
                'phone' => $phone
            ]
        );
    }
    private function parseDateTime($value)
    {
        if (empty($value)) return null;

        try {
            // Excel numeric date bo‘lishi mumkin
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
