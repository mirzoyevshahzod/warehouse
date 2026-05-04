<?php

namespace App\Http\Controllers\API;

use App\Exports\MintransExport;
use App\Http\Controllers\Controller;
use App\Models\Mintrans;
use Illuminate\Http\Request;
use App\Imports\MintransImport;
use Maatwebsite\Excel\Facades\Excel;

class MintransController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new MintransImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Mintrans Excel muvaffaqiyatli yuklandi'
        ]);
    }
    public function index(Request $request)
    {
        $query = Mintrans::query();

        // 1. Litsenziya varaqasi
        if ($request->license_number) {
            $query->where('license_number', 'like', '%' . $request->license_number . '%');
        }

        // 2. Davlat raqam
        if ($request->state_number) {
            $query->where('state_number', 'like', '%' . $request->state_number . '%');
        }

        // 3. Berilgan sana
        if ($request->date_given) {
            $query->whereDate('date_given', $request->date_given);
        }

        // 4. Korxona nomi
        if ($request->company_name) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        $mintrans = $query->orderBy('id', 'desc')
            ->paginate(30); // har page 20 ta

        return response()->json($mintrans);
    }

    public function export(Request $request)
    {
        $query = Mintrans::query();

        if ($request->license_number) {
            $query->where('license_number', 'like', '%' . $request->license_number . '%');
        }

        if ($request->state_number) {
            $query->where('state_number', 'like', '%' . $request->state_number . '%');
        }

        if ($request->company_name) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        if ($request->date_given) {
            $query->whereDate('date_given', $request->date_given);
        }

        if ($query->count() > 20000) {
            return response()->json([
                'message' => '20 000 dan ortiq ma\'lumotni eksport qilib bo\'lmaydi. Iltimos filter qo\'llang.'
            ], 400);
        }

        return Excel::download(
            new MintransExport($query->get()),
            'mintrans.xlsx'
        );
    }

}
