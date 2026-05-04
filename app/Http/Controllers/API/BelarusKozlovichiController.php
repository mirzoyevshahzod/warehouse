<?php

namespace App\Http\Controllers\API;

use App\Exports\BelarusBrestExport;
use App\Exports\BelarusKozlovichiExport;
use App\Http\Controllers\Controller;
use App\Imports\BelarusKozlovichiImport;
use App\Models\BelarusKozlovichi;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BelarusKozlovichiController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new BelarusKozlovichiImport(), $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Belarus Excel muvaffaqiyatli yuklandi'
        ]);
    }

    public function index(Request $request)
    {
        $query = BelarusKozlovichi::query();

        // 1. Qo'ng'iroq Qilish Tartibi
        if ($request->filled('call_order')) {
            $query->where('call_order', 'like', '%' . $request->call_order . '%');
        }

        // 2. Ro'yxatdan O'tish Raqami (car_number)
        if ($request->filled('car_number')) {
            $query->where('car_number', 'like', '%' . $request->car_number . '%');
        }

        // 3. ZO da Ro'yxatdan O'tgan Sana
        if ($request->filled('registration_date')) {
            $query->whereDate('date_of_registration_in_the_zo', $request->registration_date);
        }

        // 4. Holat O'zgartirildi
        if ($request->filled('status_changed')) {
            $query->whereDate('status_changed', $request->status_changed);
        }

        $belarusBenyakoni = $query->orderBy('id', 'desc')->paginate(30);

        return response()->json([
            'status' => true,
            'data' => $belarusBenyakoni
        ]);
    }



    public function export(Request $request)
    {
        return Excel::download(
            new BelarusKozlovichiExport($request),
            'belarus-Kozlovichi.xlsx'
        );
    }
}
