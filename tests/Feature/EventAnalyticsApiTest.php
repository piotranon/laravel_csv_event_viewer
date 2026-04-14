<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EventAnalyticsApiTest extends TestCase
{
    private string $csvPath;

    private string $customCsvPath;

    private ?string $originalCsv = null;

    private ?string $originalCustomCsv = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvPath = resource_path('data/events.csv');
        $this->customCsvPath = storage_path('app/data/events.custom.csv');

        if (is_file($this->csvPath)) {
            $this->originalCsv = file_get_contents($this->csvPath);
        }

        if (is_file($this->customCsvPath)) {
            $this->originalCustomCsv = file_get_contents($this->customCsvPath);
            unlink($this->customCsvPath);
        }

        if (!is_dir(dirname($this->csvPath))) {
            mkdir(dirname($this->csvPath), 0777, true);
        }

        file_put_contents($this->csvPath, $this->fixtureCsv());
    }

    protected function tearDown(): void
    {
        if ($this->originalCsv !== null) {
            file_put_contents($this->csvPath, $this->originalCsv);
        } elseif (is_file($this->csvPath)) {
            unlink($this->csvPath);
        }

        if ($this->originalCustomCsv !== null) {
            file_put_contents($this->customCsvPath, $this->originalCustomCsv);
        } elseif (is_file($this->customCsvPath)) {
            unlink($this->customCsvPath);
        }

        parent::tearDown();
    }

    public function test_events_endpoint_returns_confirmed_only_with_aggregated_ticket_sum(): void
    {
        $response = $this->getJson('/api/events');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                [
                    'event_id',
                    'event_date',
                    'city',
                    'category',
                    'confirmed_tickets_sum',
                ],
            ],
        ]);

        $events = collect($response->json('data'));

        $this->assertCount(12, $events);
        $this->assertNull($events->firstWhere('event_id', 'E013'));

        $e001 = $events->firstWhere('event_id', 'E001');
        $this->assertNotNull($e001);
        $this->assertSame('2026-04-01', $e001['event_date']);
        $this->assertSame('Warsaw', $e001['city']);
        $this->assertSame('kids', $e001['category']);
        $this->assertSame(20, $e001['confirmed_tickets_sum']);
    }

    public function test_events_endpoint_filters_by_city_date_range_and_category(): void
    {
        $response = $this->getJson('/api/events?city=Warsaw&category=kids&date_from=2026-04-01&date_to=2026-04-10');

        $response->assertOk();

        $events = collect($response->json('data'));

        $this->assertCount(2, $events);
        $this->assertSame(['E001', 'E005'], $events->pluck('event_id')->sort()->values()->all());

        foreach ($events as $event) {
            $this->assertSame('Warsaw', $event['city']);
            $this->assertSame('kids', $event['category']);
            $this->assertGreaterThanOrEqual('2026-04-01', $event['event_date']);
            $this->assertLessThanOrEqual('2026-04-10', $event['event_date']);
        }
    }

    public function test_utm_ranking_returns_top_10_sorted_descending_by_confirmed_tickets(): void
    {
        $response = $this->getJson('/api/utm-ranking');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                [
                    'utm_campaign',
                    'confirmed_tickets_sum',
                ],
            ],
        ]);

        $ranking = collect($response->json('data'));

        $this->assertCount(10, $ranking);
        $this->assertSame('camp_1', $ranking->first()['utm_campaign']);
        $this->assertSame(20, $ranking->first()['confirmed_tickets_sum']);

        $values = $ranking->pluck('confirmed_tickets_sum')->values()->all();
        $sorted = $values;
        rsort($sorted);

        $this->assertSame($sorted, $values);
        $this->assertFalse($ranking->pluck('utm_campaign')->contains('camp_11'));
        $this->assertFalse($ranking->pluck('utm_campaign')->contains('camp_12'));
    }

    public function test_upload_csv_endpoint_saves_custom_file_and_changes_active_dataset(): void
    {
        $uploadContent = implode("\n", [
            'event_id,event_date,city,category,order_id,ticket_qty,status,utm_source,utm_campaign,utm_content,sold_out',
            'U001,2026-11-01,Warsaw,kids,O9001,9,confirmed,google,upload_campaign,banner,false',
            'U001,2026-11-01,Warsaw,kids,O9002,1,confirmed,facebook,upload_campaign,story_ad,false',
            'U002,2026-11-02,Gdansk,adults,O9003,4,cancelled,newsletter,other_campaign,video_ad,false',
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('events.csv', $uploadContent);

        $response = $this
            ->withHeader('Accept', 'application/json')
            ->post('/api/events/upload-csv', ['file' => $file]);

        $response
            ->assertOk()
            ->assertJsonFragment(['message' => 'Plik CSV zostal wgrany. Uzywany jest teraz plik niestandardowy.']);

        $this->assertFileExists($this->customCsvPath);
        $this->assertGreaterThan(0, filesize($this->customCsvPath));

        $events = collect($this->getJson('/api/events')->json('data'));

        $this->assertCount(1, $events);
        $this->assertSame('U001', $events[0]['event_id']);
        $this->assertSame(10, $events[0]['confirmed_tickets_sum']);
    }

    public function test_upload_csv_endpoint_rejects_empty_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('events.csv', '');

        $response = $this
            ->withHeader('Accept', 'application/json')
            ->post('/api/events/upload-csv', ['file' => $file]);

        $response
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Plik CSV jest pusty lub nieczytelny.']);
    }

    private function fixtureCsv(): string
    {
        return implode("\n", [
            'event_id,event_date,city,category,order_id,ticket_qty,status,utm_source,utm_campaign,utm_content,sold_out',
            'E001,2026-04-01,Warsaw,kids,O1001,12,confirmed,newsletter,camp_1,banner,false',
            'E001,2026-04-01,Warsaw,kids,O1002,8,confirmed,google,camp_1,carousel,true',
            'E001,2026-04-01,Warsaw,kids,O1003,30,cancelled,facebook,camp_1,story_ad,false',
            'E002,2026-04-03,Cracow,adults,O1004,18,confirmed,google,camp_2,search_ad,false',
            'E003,2026-04-05,Gdansk,kids,O1005,16,confirmed,instagram,camp_3,video_ad,true',
            'E004,2026-04-07,Warsaw,adults,O1006,14,confirmed,newsletter,camp_4,banner,false',
            'E005,2026-04-09,Warsaw,kids,O1007,12,confirmed,google,camp_5,carousel,true',
            'E006,2026-04-11,Poznan,adults,O1008,10,confirmed,facebook,camp_6,video_ad,false',
            'E007,2026-04-13,Poznan,kids,O1009,9,confirmed,google,camp_7,search_ad,true',
            'E008,2026-04-15,Lodz,adults,O1010,8,confirmed,newsletter,camp_8,email_main,false',
            'E009,2026-04-17,Lodz,kids,O1011,7,confirmed,instagram,camp_9,story_ad,false',
            'E010,2026-04-19,Wroclaw,adults,O1012,6,confirmed,facebook,camp_10,banner,true',
            'E011,2026-04-21,Wroclaw,kids,O1013,5,confirmed,google,camp_11,video_ad,false',
            'E012,2026-04-23,Katowice,adults,O1014,4,confirmed,newsletter,camp_12,search_ad,false',
            'E013,2026-04-25,Warsaw,kids,O1015,50,cancelled,google,camp_13,video_ad,true',
        ]) . "\n";
    }
}
