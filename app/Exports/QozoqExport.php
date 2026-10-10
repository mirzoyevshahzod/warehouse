<?php

namespace App\Exports;

use App\Models\Auto;
use App\Models\Qozoq;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QozoqExport implements FromQuery, WithHeadings, WithMapping
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Qozoq::query()
            ->select([
                'id',
                'boundary_name',
                'car_number',
                'date_and_time',
                'status',
            ]);

        if ($this->request->boundary_name) {
            $boundary = str_replace(
                '–',
                '-',
                $this->request->boundary_name
            );

            $query->whereRaw(
                "REPLACE(boundary_name, '–', '-') LIKE ?",
                ["%$boundary%"]
            );
        }

        if ($this->request->car_number) {
            $query->where(
                'car_number',
                'like',
                '%' . $this->request->car_number . '%'
            );
        }

        if ($this->request->date) {
            $query->whereDate(
                'date_and_time',
                $this->request->date
            );
        }

        if ($this->request->status) {
            $query->where(
                'status',
                'like',
                '%' . $this->request->status . '%'
            );
        }

        return $query->orderBy('id', 'desc');
    }

    /**
     * Har bir Excel qatori shu yerda shakllanadi
     */
    public function map($qozoq): array
    {
        $auto = Auto::where(
            'state_number',
            $qozoq->car_number
        )->first();

        return [
            $qozoq->boundary_name,
            $qozoq->car_number,
            $qozoq->date_and_time,
            $qozoq->status,
            $auto?->company_name,
            $auto?->phone,
            'Казагистон Рассия',
        ];
    }

    public function headings(): array
    {
        return [
            'Chegara nomi',
            'Mashina raqami',
            'Sana va vaqt',
            'Status',
            'Company',
            'Telefon',
            'States',
        ];
    }
}