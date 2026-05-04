<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Test",
 *     description="Test API endpoints"
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/test",
     *     summary="Swagger test endpoint",
     *     tags={"Test"},
     *     @OA\Response(
     *         response=200,
     *         description="Test success"
     *     )
     * )
     */
    public function index()
    {
        return response()->json([
            "status" => true,
            "message" => "Swagger working"
        ]);
    }
}
