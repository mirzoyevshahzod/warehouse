<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MintransExport implements FromCollection, WithHeadings
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
                $item->model,
                $item->load_capacity,
                $item->license_number,
                $item->state_number,
                $item->company_name,
                $item->type_of_activity,
                $item->transport_type,
                $item->cargo_type,
                $item->date_given,
                $item->status,
                $item->inn,
                $item->territorial_management,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Rusumi',
            'Yuk Ko\'tarish Qobilyati',
            'Litsenziya',
            'Davlat Raqami',
            'Korxona Nomi',
            'Faoliyat Turi',
            'Transport Turi',
            'Yuk Turi',
            'Berilgan Sana',
            'Holati',
            'INN',
            'Hududiy Boshqarma',
        ];
    }
}
