<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvFileManager
{
    public function getDefaultPath(): string
    {
        return resource_path('data/events.csv');
    }

    public function getCustomPath(): string
    {
        return storage_path('app/data/events.custom.csv');
    }

    public function getActivePath(): string
    {
        return is_file($this->getCustomPath()) ? $this->getCustomPath() : $this->getDefaultPath();
    }

    public function replaceWithUploaded(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new RuntimeException('Wgrany plik jest niepoprawny.');
        }

        $targetPath = $this->getCustomPath();
        $directory = dirname($targetPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $realPath = $file->getRealPath();

        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException('Nie mozna odczytac przeslanego pliku.');
        }

        $content = file_get_contents($realPath);

        if ($content === false || trim($content) === '') {
            throw new RuntimeException('Plik CSV jest pusty lub nieczytelny.');
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $headerLine = $lines[0] ?? '';
        $header = str_getcsv($headerLine);

        if ($header === false || $header === [null] || $header === []) {
            throw new RuntimeException('Nie mozna odczytac naglowka CSV.');
        }

        $requiredColumns = [
            'event_id',
            'event_date',
            'city',
            'category',
            'order_id',
            'ticket_qty',
            'status',
            'utm_source',
            'utm_campaign',
            'utm_content',
            'sold_out',
        ];

        $normalizedHeader = array_map(static fn ($col) => trim((string) $col), $header);

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                throw new RuntimeException('Brak wymaganej kolumny: ' . $column);
            }
        }

        $tmpPath = $targetPath . '.tmp';
        $bytes = file_put_contents($tmpPath, $content, LOCK_EX);

        if ($bytes === false || $bytes === 0) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            throw new RuntimeException('Nie udalo sie zapisac pliku CSV.');
        }

        if (!rename($tmpPath, $targetPath)) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            throw new RuntimeException('Nie udalo sie finalizowac zapisu pliku CSV.');
        }
    }

    public function resetToDefault(): void
    {
        $customPath = $this->getCustomPath();

        if (is_file($customPath)) {
            unlink($customPath);
        }
    }
}
