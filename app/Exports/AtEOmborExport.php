<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AtEOmborExport implements FromCollection, WithHeadings
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
                $item->document_number,
                $item->custom_code,
                $item->custom_date,
                $item->tebhn_number,
                $item->transport_number,
                $item->gross_weight,
                $item->inn,
                $item->recipient_name,
                $item->delivery_post,
                $item->delivery_date,
                $item->arrival_place,
                $item->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Document Number',
            'Custom Code',
            'Custom Date',
            'TEBHN Number',
            'Transport Number',
            'Gross Weight',
            'INN',
            'Recipient Name',
            'Delivery Post',
            'Delivery Date',
            'Arrival Place',
            'Status',
        ];
    }
}

