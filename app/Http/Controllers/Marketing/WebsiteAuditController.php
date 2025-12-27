<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\WebsiteAudit;
use Barryvdh\DomPDF\Facade\Pdf;

class WebsiteAuditController extends Controller
{
    public function pdf(WebsiteAudit $websiteAudit)
    {
        $user = auth()->user();
        if ($user?->isClient() && $websiteAudit->client_id !== $user->client_id) {
            abort(403);
        }

        $websiteAudit->load(['issues', 'pages']);

        $pdf = Pdf::loadView('marketing.audits.website-audit-pdf', [
            'audit' => $websiteAudit,
            'report' => (array) ($websiteAudit->report ?? []),
        ])->setPaper('a4', 'portrait');

        $filename = 'website-audit-' . $websiteAudit->id . '.pdf';
        return response()->streamDownload(fn () => print($pdf->output()), $filename, ['Content-Type' => 'application/pdf']);
    }
}

