<?php

namespace App\Services\AdminReports\Exports;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetArrayExport implements WithMultipleSheets
{
    public function __construct(private readonly array $payload)
    {
    }

    public function sheets(): array
    {
        $tables = Arr::get($this->payload, 'tables', []);
        $sheets = [];

        foreach ($tables as $name => $rows) {
            $sheets[] = new SingleSheetArrayExport((string) $name, (array) $rows);
        }

        if (empty($sheets)) {
            $sheets[] = new SingleSheetArrayExport('Report', []);
        }

        return $sheets;
    }
}

