<?php

namespace App\Models;

use App\Services\CsvFileManager;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class CsvEvent extends Model
{
    use Sushi;

    protected $sushiShouldCache = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ticket_qty' => 'integer',
            'sold_out' => 'boolean',
        ];
    }

    public function getRows(): array
    {
        $path = app(CsvFileManager::class)->getActivePath();

        if (!is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(static fn (string $col): string => trim($col), $header);

        $rows = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if (count($data) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $data);

            if ($row === false) {
                continue;
            }

            $row['id'] = 'csv_' . $line;
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
