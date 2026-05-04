<?php

namespace App\Exports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class DriverExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Driver::query();

        if (!empty($this->filters['full_name'])) {
            $query->where('full_name', 'LIKE', '%' . $this->filters['full_name'] . '%');
        }

        if (!empty($this->filters['number'])) {
            $query->where('number', 'LIKE', '%' . $this->filters['number'] . '%');
        }

        if (!empty($this->filters['phone'])) {
            $query->where('phone', 'LIKE', '%' . $this->filters['phone'] . '%');
        }

        if (!empty($this->filters['trailer_number'])) {
            $query->where('trailer_number', 'LIKE', '%' . $this->filters['trailer_number'] . '%');
        }

        return $query->get([
            'full_name',
            'number',
            'phone',
            'length',
            'width',
            'height',
            'trailer_number',
            'capacity',
            'lifting',
            'brand',
        ]);


    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Number',
            'Phone',
            'Length',
            'Width',
            'Height',
            'Trailer Number',
            'Capacity',
            'Lifting',
            'Brand',
        ];
    }   
}
