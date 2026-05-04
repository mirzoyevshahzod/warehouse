<?php

namespace App\Http\Controllers;

use App\Exports\AutosExport;
use Maatwebsite\Excel\Facades\Excel;

class AutoController extends Controller
{
    public function export()
    {
        return Excel::download(new AutosExport, 'autos.xlsx');
    }
}
