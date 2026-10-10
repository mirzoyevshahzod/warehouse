<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Transport Monitoring API",
 *     version="1.0.0",
 *     description="API Documentation"
 * )
 *
 * @OA\Server(
 *     url="https://warehouse.izisol.uz",
 *     description="Local Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class OpenApi
{
}
