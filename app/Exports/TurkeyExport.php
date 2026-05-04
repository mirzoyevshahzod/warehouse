<?php

namespace App\Exports;

use App\Models\Turkey;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TurkeyExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Turkey::query();

        if (!empty($this->filters['input_sequence_number'])) {
            $query->where('input_sequence_number', $this->filters['input_sequence_number']);
        }

        if (!empty($this->filters['car_number'])) {
            $query->where('car_number', 'LIKE', '%' . $this->filters['car_number'] . '%');
        }

        if (!empty($this->filters['company_name'])) {
            $query->where('company_name', 'LIKE', '%' . $this->filters['company_name'] . '%');
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('date', '>=', $this->filters['date_from'] . ' 00:00:00');
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('date', '<=', $this->filters['date_to'] . ' 23:59:59');
        }

        if (!empty($this->filters['phone'])) {
            $query->where('phone', 'LIKE', '%' . $this->filters['phone'] . '%');
        }
     

        return $query->get([
            'ordinal_number',
            'input_sequence_number',
            'car_number',
            'date',
            'entrance',
            'company_name',
            'phone'
        ]);
    }

    public function headings(): array
    {
        return [
            'Tartib Raqam',
            'Kirish Tartib Raqami',
            'Avtomobil Raqami',
            'Sana',
            'Kirish Joyi',
            'Tashkilot Nomi',
            'Telefon'
        ];
    }
}
