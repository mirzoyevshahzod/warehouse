<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Support\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Dashboard: qayerda qancha ma'lumot bor, qachon qancha qo'shildi, oxirgi yuklashlar */
class DashboardController extends Controller
{
    /** Katta jadvallarni har safar sanamaslik uchun natija shuncha soniya saqlanadi */
    private const CACHE_SECONDS = 60;

    /**
     * @OA\Get(
     *     path="/api/dashboard/stats",
     *     summary="Manbalar bo'yicha statistika: jami, bugun/hafta/oy qo'shilgani, oxirgi yangilanish, kunlik grafik, O'zbekiston firmalari",
     *     tags={"Dashboard"},
     *     @OA\Parameter(name="fresh", in="query", description="1 — keshni chetlab, qayta hisoblash", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Statistika")
     * )
     */
    public function stats(Request $request)
    {
        if ($request->boolean('fresh')) {
            Cache::forget('dashboard.stats');
        }

        $data = Cache::remember('dashboard.stats', self::CACHE_SECONDS, fn () => (new DashboardStats)->overview());

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/uploads",
     *     summary="Oxirgi fayl yuklashlar (barcha manbalar)",
     *     tags={"Dashboard"},
     *     @OA\Parameter(name="limit", in="query", description="Default 15, max 100", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="[{source, label, file_name, status, added, rows, error, duration_ms, user, created_at}]")
     * )
     */
    public function uploads(Request $request)
    {
        $request->validate(['limit' => 'nullable|integer|min:1|max:100']);

        return response()->json([
            'status' => true,
            'data' => (new DashboardStats)->recentUploads((int) $request->input('limit', 15)),
        ]);
    }
}
