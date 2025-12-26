<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReports\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportExportController extends Controller
{
    public function __construct(private readonly ReportDataService $reportData)
    {
    }

    /**
     * Export admin reports as CSV, XLSX, or PDF.
     */
    public function export(Request $request, string $category, string $format): Response|StreamedResponse
    {
        $params = $request->all();
        // Allow metrics filtering via query (?metrics[]=... or ?metrics=a,b,c)
        if ($request->has('metrics')) {
            $params['metrics'] = $request->input('metrics');
        }

        $payload = $this->reportData->build($category, $params);

        return match ($format) {
            'csv' => $this->exportCsv($category, $payload),
            'xlsx' => $this->exportXlsx($category, $payload),
            'pdf' => $this->exportPdf($category, $payload),
            default => abort(404),
        };
    }

    protected function exportCsv(string $category, array $payload): StreamedResponse
    {
        $filename = $this->filename($category, 'csv');
        $tables = Arr::get($payload, 'tables', []);

        return response()->streamDownload(function () use ($tables) {
            $out = fopen('php://output', 'wb');
            foreach ($tables as $tableName => $rows) {
                fputcsv($out, [$tableName]);
                fputcsv($out, []);

                if (empty($rows)) {
                    fputcsv($out, ['(no data)']);
                    fputcsv($out, []);
                    continue;
                }

                $header = array_keys((array) $rows[0]);
                fputcsv($out, $header);

                foreach ($rows as $row) {
                    $row = (array) $row;
                    fputcsv($out, array_map(fn ($key) => $row[$key] ?? null, $header));
                }

                fputcsv($out, []);
                fputcsv($out, []);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function exportXlsx(string $category, array $payload): Response|StreamedResponse
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            // Graceful fallback if Excel package isn't installed in this environment.
            return $this->exportCsv($category, $payload);
        }

        $filename = $this->filename($category, 'xlsx');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Services\AdminReports\Exports\MultiSheetArrayExport($payload),
            $filename
        );
    }

    protected function exportPdf(string $category, array $payload): Response
    {
        $filename = $this->filename($category, 'pdf');
        $view = "admin.reports.exports.{$category}";

        if (!view()->exists($view)) {
            $view = 'admin.reports.exports.generic';
        }

        $pdf = Pdf::loadView($view, [
            'category' => $category,
            'payload' => $payload,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    protected function filename(string $category, string $ext): string
    {
        return sprintf('report_%s_%s.%s', $category, now()->format('Ymd_His'), $ext);
    }
}

