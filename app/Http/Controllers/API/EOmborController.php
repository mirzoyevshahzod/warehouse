<?php

namespace App\Http\Controllers\API;

use App\Exports\AtEOmborExport;
use App\Http\Controllers\Controller;
use App\Models\EOmbor;
use Illuminate\Http\Request;
use App\Imports\ATEomborImport;
use Maatwebsite\Excel\Facades\Excel;

class EOmborController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ATEomborImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Excel muvaffaqiyatli yuklandi'
        ]);
    }

    public function index(Request $request)
    {
        $query = Eombor::query()->orderBy('document_number', 'desc');

        // 1. Hujjat raqam
        if ($request->document_number) {
            $query->where('document_number', 'like', '%' . $request->document_number . '%');
        }

        // 2. Vaqt (custom_date)
        if ($request->custom_date) {
            $query->whereDate('custom_date', $request->custom_date);
        }

        // 3. Avto raqam
        if ($request->transport_number) {
            $query->where('transport_number', 'like', '%' . $request->transport_number . '%');
        }

        // 4. INN
        if ($request->inn) {
            $query->where('inn', 'like', '%' . $request->inn . '%');
        }

        $eombors = $query->paginate(50);

        return response()->json([
            'status' => true,
            'eombors' => $eombors
        ]);
    }

    public function export(Request $request)
    {
        $query = EOmbor::query();

        if ($request->document_number) {
            $query->where('document_number', 'like', '%' . $request->document_number . '%');
        }

        if ($request->custom_date) {
            $query->whereDate('custom_date', $request->custom_date);
        }

        if ($request->transport_number) {
            $query->where('transport_number', 'like', '%' . $request->transport_number . '%');
        }

        if ($request->inn) {
            $query->where('inn', 'like', '%' . $request->inn . '%');
        }

        if ($query->count() > 20000) {
            return response()->json([
                'message' => '20 000 dan ortiq ma\'lumotni eksport qilib bo\'lmaydi'
            ], 400);
        }

        return Excel::download(
            new AtEOmborExport($query->get()),
            'rw-e-ombor-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );

    }

}
