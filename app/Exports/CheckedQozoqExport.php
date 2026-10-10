<?php

namespace App\Exports;

use App\Models\CheckedQozoq;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CheckedQozoqExport implements FromQuery, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

   public function query()
{
    $query = CheckedQozoq::query()
        ->select([
            'boundary_name',
            'car_number',
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

    // 🔥 SANANI QO'SHING
    if ($this->request->date_from) {
        $query->where('date_and_time', '>=', $this->request->date_from);
    }

    if ($this->request->date_to) {
        $query->where('date_and_time', '<=', $this->request->date_to);
    }
    // 🔥 STATUSNI QO'SHING
    if ($this->request->status) {
        $query->where('status', 'like', '%' . $this->request->status . '%');
    }

    if ($this->request->car_type) {

        if ($this->request->car_type === 'uz') {
            $query->whereRaw("
                car_number REGEXP '^[0-9]{2}[A-Z][0-9]{3}[A-Z]{2}$'
            ");
        }

        if ($this->request->car_type === 'foreign') {
            $query->whereRaw("
                car_number NOT REGEXP '^[0-9]{2}[A-Z][0-9]{3}[A-Z]{2}$'
            ");
        }
    }

    if ($this->request->company_name !== null && $this->request->company_name !== '') {

        $company = trim($this->request->company_name);

        if ($company === '-') {
            $query->where(function ($q) {
                $q->whereNull('company_name')
                  ->orWhereRaw("TRIM(company_name) = ''")
                  ->orWhere('company_name', '-');
            });
        } else {
            $query->where('company_name', 'like', '%' . $company . '%');
        }
    }

        


    return $query->orderBy('id', 'desc');
}


    public function headings(): array
    {
        return [
            'Chegara nomi',
            'Avto raqami',
            'Tashkilot',
            'Sana',
            'Holati'
        ];
    }
}
