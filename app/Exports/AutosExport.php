<?php

namespace App\Exports;

use App\Models\Auto;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\Schema;

class AutosExport implements FromQuery, WithHeadings
{
    use Exportable;

    public function query()
    {
        return Auto::query();
    }

    public function headings(): array
    {
        return Schema::getColumnListing('autos');
    }
}
