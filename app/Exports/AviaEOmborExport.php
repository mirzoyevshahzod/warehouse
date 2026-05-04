<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AviaEOmborExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            return [
                $item->air_waybill_number,
                $item->flight_number,
                $item->registration_date,
                $item->consignee,
                $item->total_net_weight,
                $item->border_customs_post_code,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Air Waybill Number',
            'Flight Number',
            'Registration Date',
            'Consignee',
            'Total Net Weight',
            'Border Customs Post Code',
        ];
    }
}
