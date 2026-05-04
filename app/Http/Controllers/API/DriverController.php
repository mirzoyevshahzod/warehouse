<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DriversImport;
use App\Models\Driver;
use App\Exports\DriverExport;

class DriverController extends Controller
{


    public function importDrivers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new DriversImport, $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Import successful'
        ]);
    }


    public function index(Request $request)
    {
        $drivers = Driver::query()
            ->when($request->full_name, fn($q) =>
                $q->where('full_name', 'like', "%{$request->full_name}%")
            )
            ->when($request->number, fn($q) =>
                $q->where('number', 'like', "%{$request->number}%")
            )
            ->when($request->phone, fn($q) =>
                $q->where('phone', 'like', "%{$request->phone}%")
            )
            ->when($request->trailer_number, fn($q) =>
                $q->where('trailer_number', 'like', "%{$request->trailer_number}%")
            )
            ->latest()
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new DriverExport($request->only([
                'full_name',
                'number',
                'phone',
                'trailer_number'
            ])),
            'drivers.xlsx'
        );
    }
}
