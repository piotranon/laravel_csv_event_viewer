<?php

namespace App\Services;

use App\Models\CsvEvent;
use Illuminate\Database\Eloquent\Builder;

class CsvEventAnalyticsService
{
    public function getEventSummaries(array $filters = []): array
    {
        $query = CsvEvent::query()->where('status', 'confirmed');
        $this->applyFilters($query, $filters);

        return $query
            ->get()
            ->groupBy('event_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'event_id' => $first->event_id,
                    'event_date' => $first->event_date,
                    'city' => $first->city,
                    'category' => $first->category,
                    'confirmed_tickets_sum' => (int) $rows->sum('ticket_qty'),
                ];
            })
            ->sortBy('event_date')
            ->values()
            ->all();
    }

    public function getTopUtmCampaigns(int $limit = 10): array
    {
        return CsvEvent::query()
            ->where('status', 'confirmed')
            ->get()
            ->groupBy(function ($row): string {
                $campaign = trim((string) $row->utm_campaign);

                return $campaign === '' ? 'unknown' : $campaign;
            })
            ->map(fn ($rows, string $campaign): array => [
                'utm_campaign' => $campaign,
                'confirmed_tickets_sum' => (int) $rows->sum('ticket_qty'),
            ])
            ->sortByDesc('confirmed_tickets_sum')
            ->take($limit)
            ->values()
            ->all();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $city = trim((string) ($filters['city'] ?? ''));
        if ($city !== '') {
            $query->where('city', $city);
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $query->where('category', $category);
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->where('event_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->where('event_date', '<=', $dateTo);
        }
    }
}
