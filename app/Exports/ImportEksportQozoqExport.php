<?php

namespace App\Exports;

use App\Models\ImportEksportQozoq;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportEksportQozoqExport implements FromQuery, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;  
    }

    public function query()
    {
        $query = ImportEksportQozoq::query()
            ->select([
                'company_name',     // 👈 birinchi qo‘yildi
                'boundary_name',
                'car_number',
                'date_and_time',
                'status'
            ]);

        if ($this->request->boundary_name) {
            $boundary = str_replace('–', '-', $this->request->boundary_name);

            $query->whereRaw("
                REPLACE(boundary_name, '–', '-')
                LIKE ?
            ", ["%$boundary%"]);
        }

        if ($this->request->car_number) {
            $query->where('car_number', 'like', '%' . $this->request->car_number . '%');
        }

        if ($this->request->date) {
            $query->whereDate('date_and_time', $this->request->date);
        }

        if ($this->request->status) {
            $query->where('status', 'like', '%' . $this->request->status . '%');
        }
        if ($this->request->company_name !== null && $this->request->company_name !== '') {
            $company = trim($this->request->company_name);
            $query->where('company_name', 'like', '%' . $company . '%');
        }

        return $query->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'Kompaniya nomi',
            'Chegara nomi',
            'Mashina raqami',
            'Sana va vaqt',
            'Status',
        ];
    }
}
