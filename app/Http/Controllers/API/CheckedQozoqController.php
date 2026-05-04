<?php

namespace App\Http\Controllers\API;

use App\Exports\CheckedQozoqExport;
use App\Http\Controllers\Controller;
use App\Models\CheckedQozoq;
use Illuminate\Http\Request;
use App\Imports\CheckedQozoqImport;
use OpenApi\Annotations as OA;
use Maatwebsite\Excel\Facades\Excel;



class CheckedQozoqController extends Controller
{
/**
 * @OA\Post(
 *     path="/api/checked-qozoq/import",
 *     summary="Excel orqali Qozoq ma'lumotlarini yuklash",
 *     tags={"Eksport"},
 *     security={ {"bearerAuth": {}} },
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"file"},
 *                 @OA\Property(
 *                     property="file",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Excel muvaffaqiyatli yuklandi"
 *     )
 * )
 */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new CheckedQozoqImport, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Qozoq Excel muvaffaqiyatli yuklandi'
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/checked-qozoq",
     *     summary="Qozoq transport ro'yxatini olish",
     *     tags={"Eksport"},
     *      security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="boundary_name",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="car_number",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="company_name",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="car_type",
     *         in="query",
     *         required=false,
     *         description="uz yoki foreign",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Qozoq ma'lumotlari"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = CheckedQozoq::query();

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
     *     path="/api/checked-qozoq/export",
     *     summary="Qozoq ma'lumotlarini Excel qilib yuklab olish",
     *     tags={"Eksport"},
     *      security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Excel file yuklanadi"
     *     )
     * )
     */
    public function export(Request $request)
    {
        return Excel::download(
            new CheckedQozoqExport($request),
            'qozoq.xlsx'
        );
    }


}
