<?php

namespace App\Exports;

use App\Models\BelarusBenyakoni;
use App\Models\BelarusBrest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BelarusBrestExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = BelarusBrest::query();

        if ($this->request->filled('call_order')) {
            $query->where('call_order', 'like', '%' . $this->request->call_order . '%');
        }

        if ($this->request->filled('car_number')) {
            $query->where('car_number', 'like', '%' . $this->request->car_number . '%');
        }

        if ($this->request->filled('registration_date')) {
            $query->whereDate('date_of_registration_in_the_zo', $this->request->registration_date);
        }

        if ($this->request->filled('status_changed')) {
            $query->whereDate('status_changed', $this->request->status_changed);
        }

        return $query->get([
            'call_order',
            'queue_type',
            'car_number',
            'date_of_registration_in_the_zo',
            'status_changed',
            'status',
            'company_name'
        ]);
    }

    public function headings(): array
    {
        return [
            "Qo'ng'iroq Tartibi",
            "Navbat Turi",
            "Ro'yxatdan O'tish Raqami",
            "ZO Ro'yxat Sana",
            "Holat O'zgartirildi",
            "Holat",
            "Tashkilot Nomi"
        ];
    }
}
