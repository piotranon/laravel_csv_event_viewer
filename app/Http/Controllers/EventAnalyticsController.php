<?php

namespace App\Http\Controllers;

use App\Services\CsvEventAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventAnalyticsController extends Controller
{
    public function __construct(private readonly CsvEventAnalyticsService $analyticsService)
    {
    }

    public function events(Request $request): JsonResponse
    {
        $filters = [
            'city' => $request->query('city'),
            'category' => $request->query('category'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        return response()->json([
            'data' => $this->analyticsService->getEventSummaries($filters),
        ]);
    }

    public function utmRanking(): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getTopUtmCampaigns(10),
        ]);
    }
}
