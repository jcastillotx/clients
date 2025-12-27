<?php

namespace App\Services\AdminReports\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SingleSheetArrayExport implements FromCollection, WithHeadings, WithTitle
{
    private array $rows;

    private array $headings;

    public function __construct(private readonly string $title, array $rows)
    {
        $this->rows = array_map(fn ($r) => (array) $r, $rows);
        $this->headings = $this->rows ? array_keys($this->rows[0]) : ['(no data)'];
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        if (! $this->rows) {
            return collect([['(no data)' => '(no data)']]);
        }

        return collect($this->rows)->map(function (array $row) {
            return collect($this->headings)->mapWithKeys(fn ($h) => [$h => $row[$h] ?? null]);
        });
    }
}
