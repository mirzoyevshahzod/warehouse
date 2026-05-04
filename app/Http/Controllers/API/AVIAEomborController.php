<?php

namespace App\Http\Controllers\API;

use App\Exports\AviaEOmborExport;
use App\Http\Controllers\Controller;
use App\Imports\AviaEomborImport;
use App\Models\AVIAEOmbor;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AVIAEomborController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new AviaEomborImport, $request->file('file'));

            return response()->json([
                'status' => true,
                'message' => 'Excel muvaffaqiyatli yuklandi'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = AVIAEOmbor::query();

        // 1. Air Waybill Number
        if ($request->filled('air_waybill_number')) {
            $query->where('air_waybill_number', 'like', '%' . $request->air_waybill_number . '%');
        }

        // 2. Registration Date
        if ($request->filled('registration_date')) {
            $query->whereDate('registration_date', $request->registration_date);
        }

        // 3. Flight Number
        if ($request->filled('flight_number')) {
            $query->where('flight_number', 'like', '%' . $request->flight_number . '%');
        }

        // 4. Border Customs Post Code
        if ($request->filled('border_customs_post_code')) {
            $query->where('border_customs_post_code', 'like', '%' . $request->border_customs_post_code . '%');
        }

        $eombors = $query->orderByDesc('id')->paginate(50);

        return response()->json([
            'status' => true,
            'eombors' => $eombors
        ]);
    }

    public function export(Request $request)
    {
        $query = AVIAEOmbor::query();

        if ($request->air_waybill_number) {
            $query->where('air_waybill_number', 'like', '%' . $request->air_waybill_number . '%');
        }

        if ($request->registration_date) {
            $query->whereDate('registration_date', $request->registration_date);
        }

        if ($request->flight_number) {
            $query->where('flight_number', 'like', '%' . $request->flight_number . '%');
        }

        if ($request->border_customs_post_code) {
            $query->where('border_customs_post_code', 'like', '%' . $request->border_customs_post_code . '%');
        }

        // 20k limit
        if ($query->count() > 20000) {
            return response()->json([
                'message' => '20 000 dan ortiq ma\'lumotni eksport qilib bo\'lmaydi'
            ], 400);
        }

        return Excel::download(
            new AviaEOmborExport($query->get()),
            'avia-e-ombor-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

}
