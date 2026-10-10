<?php

namespace App\Http\Controllers\API;

use App\Exports\ImportQozoqExport;
use App\Http\Controllers\Controller;
use App\Models\ImportQozoq;
use Illuminate\Http\Request;
use App\Imports\ImportQozoqImport;
use Maatwebsite\Excel\Facades\Excel;



class ImportQozoqController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/import-qozoq/import",
     *     summary="Excel fayl yuklash (Qozoq)",
     *     tags={"Import Qozoq"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Excel fayl (xlsx, xls, csv)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fayl muvaffaqiyatli yuklandi"
     *     )
     * )
     */
    public function import(Request $request)
    {
         ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ImportQozoqImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Qozoq Excel muvaffaqiyatli yuklandi'
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/import-qozoq",
     *     summary="Qozoq import list (filter bilan)",
     *     tags={"Import Qozoq"},
     *     @OA\Parameter(
     *         name="boundary_name",
     *         in="query",
     *         description="Chegara nomi",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="car_number",
     *         in="query",
     *         description="Mashina raqami",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Sana (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="company_name",
     *         in="query",
     *         description="Kompaniya nomi yoki '-'",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="car_type",
     *         in="query",
     *         description="uz yoki foreign",
     *         required=false,
     *         @OA\Schema(type="string", enum={"uz","foreign"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List qaytadi"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = ImportQozoq::query()->orderBy('date_and_time', 'desc');

        if ($request->boundary_name) {

            // 🔥 Tire normalize qilamiz
            $boundary = str_replace('–', '-', $request->boundary_name);

            $query->whereRaw("
            REPLACE(boundary_name, '–', '-')
            LIKE ?
        ", ["%$boundary%"]);
        }

        if ($request->car_number) {
            $query->where('car_number', 'like', '%' . $request->car_number . '%');
        }

          if ($request->date_from) {
            $query->where('date_and_time', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->date_to) {
            $query->where('date_and_time', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->status) {
            $query->where('status', 'like', '%' . $request->status . '%');
        }

        if ($request->company_name !== null && $request->company_name !== '') {

            $company = trim($request->company_name);

            // 🔥 Agar "-" yozilsa → kompaniya mavjud bo‘lmaganlar
            if ($company === '-') {
                $query->where(function ($q) {
                    $q->whereNull('company_name')
                    ->orWhere('company_name', '')
                    ->orWhere('company_name', '-');
                });
            } else {
                $query->where('company_name', 'like', '%' . $company . '%');
            }
        }

        if ($request->car_type) {

            if ($request->car_type === 'uz') {
                $query->whereRaw("
                    car_number REGEXP '^[0-9]{2}[A-Z][0-9]{3}[A-Z]{2}$'
                ");
            }

            if ($request->car_type === 'foreign') {
                $query->whereRaw("
                    car_number NOT REGEXP '^[0-9]{2}[A-Z][0-9]{3}[A-Z]{2}$'
                ");
            }
        }





        $data = $query->orderBy('id','desc')->paginate(30);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/import-qozoq/export",
     *     summary="Excel export qilish",
     *     tags={"Import Qozoq"},
     *     @OA\Parameter(
     *         name="boundary_name",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel fayl yuklab olinadi",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     )
     * )
     */

    public function export(Request $request)
    {
        return Excel::download(
            new ImportQozoqExport($request),
            'qozoq.xlsx'
        );
    }


}
