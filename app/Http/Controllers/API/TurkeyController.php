<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Turkey;
use Illuminate\Http\Request;
use App\Imports\TurkeyImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TurkeyExport;


class TurkeyController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new TurkeyImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Turkey Excel muvaffaqiyatli yuklandi'
        ]);
    }


    public function index(Request $request)
    {
        $query = Turkey::query();

        if ($request->filled('input_sequence_number')) {
            $query->where('input_sequence_number', $request->input_sequence_number);
        }

        if ($request->filled('car_number')) {
            $query->where('car_number', 'LIKE', '%' . $request->car_number . '%');
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', 'LIKE', '%' . $request->company_name . '%');
        }

         if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to . ' 23:59:59');
        }

        return response()->json([
            'data' => $query->orderBy('id', 'desc')->paginate(10)
        ]);
    }

    public function export(Request $request)
    {
        $query = Turkey::query()->orderBy('date','desc');

        if ($request->input_sequence_number) {
            $query->where('input_sequence_number', $request->input_sequence_number);
        }

        if ($request->car_number) {
            $query->where('car_number', 'LIKE', '%' . $request->car_number . '%');
        }

        if ($request->company_name) {
            $query->where('company_name', 'LIKE', '%' . $request->company_name . '%');
        }

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        if ($query->count() > 20000) {
            return response()->json([
                'message' => '20 000 dan ortiq ma\'lumotni eksport qilib bo\'lmaydi. Iltimos filter qo\'llang.'
            ], 400);
        }

        return Excel::download(
            new TurkeyExport($request->all()),
            'turkey.xlsx'
        );
    }


}
