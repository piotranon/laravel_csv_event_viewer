<?php

namespace App\Http\Controllers;

use App\Services\CsvEventAnalyticsService;
use App\Services\CsvFileManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EventAnalyticsController extends Controller
{
    public function __construct(
        private readonly CsvEventAnalyticsService $analyticsService,
        private readonly CsvFileManager $csvFileManager
    ) {}

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

    public function uploadCsv(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:5120',
            ],
        ]);

        try {
            $this->csvFileManager->replaceWithUploaded($validated['file']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Plik CSV zostal wgrany. Uzywany jest teraz plik niestandardowy.',
        ]);
    }

    public function resetCsv(): JsonResponse
    {
        $this->csvFileManager->resetToDefault();

        return response()->json([
            'message' => 'Przywrocono domyslny plik CSV.',
        ]);
    }
}
