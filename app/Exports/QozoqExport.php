<?php

namespace App\Exports;

use App\Models\Qozoq;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QozoqExport implements FromQuery, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;  
    }

    public function query()
    {
        $query = Qozoq::query()
            ->select([
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

        return $query->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'Chegara nomi',
            'Mashina raqami',
            'Sana va vaqt',
            'Status'
        ];
    }
}
