<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetArrayExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{title:string, headings:array<int,string>, rows:array<int,array<int,mixed>>}>  $datasets
     */
    public function __construct(protected array $datasets) {}

    public function sheets(): array
    {
        return array_map(
            fn ($d) => new SheetFromArray(
                (string) ($d['title'] ?? 'Sheet'),
                (array) ($d['headings'] ?? []),
                (array) ($d['rows'] ?? []),
            ),
            $this->datasets
        );
    }
}

