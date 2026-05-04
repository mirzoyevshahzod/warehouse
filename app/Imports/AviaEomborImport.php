<?php

namespace App\Imports;

use App\Models\AVIAEOmbor;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AviaEomborImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new AVIAEOmbor([
            'air_waybill_number' => $row['air_waybill_number'],
            'flight_number' => $row['flight_number'],
            'registration_date' => isset($row['registration_date'])
                ? Carbon::parse($row['registration_date'])->format('Y-m-d')
                : null,
            'consignee' => $row['consignee'],
            'total_net_weight' => $row['total_net_weight'],
            'border_customs_post_code' => $row['border_customs_post_code'],
        ]);
    }
}
