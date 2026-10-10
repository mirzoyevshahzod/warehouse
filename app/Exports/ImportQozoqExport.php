<?php

namespace App\Exports;

use App\Models\ImportQozoq;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportQozoqExport implements FromQuery, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;  
    }

    public function query()
    {
        $query = ImportQozoq::query()
            ->select([
                'boundary_name',
                'car_number',     // 👈 birinchi qo‘yildi
                'company_name',
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

        if ($this->request->date_from) {
            $query->where('date_and_time', '>=', $this->request->date_from);
        }

        if ($this->request->date_to) {
            $query->where('date_and_time', '<=', $this->request->date_to);
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
            'Chegara nomi',
            'Mashina raqami',
            'Tashkilot',
            'Sana va vaqt',
            'Status'
        ];
    }
}
