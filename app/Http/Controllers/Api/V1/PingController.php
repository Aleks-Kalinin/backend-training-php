<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    /**
     * A minimal example endpoint: GET /api/v1/ping.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'time' => now()->toIso8601ZuluString(),
        ]);
    }
}
