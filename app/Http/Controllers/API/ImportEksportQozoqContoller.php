<?php

namespace App\Http\Controllers\API;

use App\Exports\ImportEksportQozoqExport;
use App\Http\Controllers\Controller;
use App\Models\ImportEksportQozoq;
use Illuminate\Http\Request;
use App\Imports\ImportEksportQozoqImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportEksportQozoqContoller extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/import-eksport-qozoq/import",
     *     summary="Excel fayl yuklash (Qozoq)",
     *     tags={"Import Eksport Qozoq"},
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
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ImportEksportQozoqImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Qozoq Excel muvaffaqiyatli yuklandi'
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/import-eksport-qozoq",
     *     summary="Qozoq import list (filter bilan)",
     *     tags={"Import Eksport Qozoq"},
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
        $query = ImportEksportQozoq::query()->orderBy('date_and_time', 'desc');

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

       // Sana oralig'i
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date_and_time', [
                $request->from_date,
                $request->to_date,
            ]);
        } elseif ($request->filled('from_date')) {
            $query->where('date_and_time', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->where('date_and_time', '<=', $request->to_date);
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
     *     path="/api/import-eksport-qozoq/export",
     *     summary="Excel export qilish",
     *     tags={"Import Eksport Qozoq"},
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
            new ImportEksportQozoqExport($request),
            'qozoq.xlsx'
        );
    }

}
