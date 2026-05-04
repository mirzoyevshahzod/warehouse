<?php

namespace App\Http\Controllers\API;

use App\Exports\BelarusBrestExport;
use App\Exports\BelarusGigorovschinaExport;
use App\Http\Controllers\Controller;
use App\Imports\BelarusGigorovschinaImport;
use App\Models\BelarusGrigorovschina;
use Illuminate\Http\Request;
use App\Imports\BelarusBrestImport;
use Maatwebsite\Excel\Facades\Excel;

class BelarusGigorovschinaController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new BelarusGigorovschinaImport(), $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Belarus Excel muvaffaqiyatli yuklandi'
        ]);
    }

    public function index(Request $request)
    {
        $query = BelarusGrigorovschina::query();

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
            new BelarusGigorovschinaExport($request),
            'belarus-Gigorovschina.xlsx'
        );
    }
}
