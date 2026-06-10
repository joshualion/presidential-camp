<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardStatsController extends Controller
{
    public function cards(Request $request, DashboardStatSnapshotService $snapshotService): JsonResponse
    {
        return response()->json(
            $snapshotService->payloadForUser($request->user())
        );
    }
}
