<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DocumentTextExtractor
{
    /**
     * Extract best-effort text from a stored file.
     *
     * @return array{text:string, method:string, warnings:array<int,string>}
     */
    public function extractFromStorage(string $disk, string $path, ?string $mimeType = null, ?string $originalFilename = null): array
    {
        $warnings = [];
        $mimeType = $mimeType ?: null;
        $ext = strtolower(pathinfo((string) ($originalFilename ?: $path), PATHINFO_EXTENSION));

        try {
            $fullPath = Storage::disk($disk)->path($path);
        } catch (\Throwable) {
            $fullPath = null;
        }

        // Plain text
        if ($mimeType === 'text/plain' || in_array($ext, ['txt', 'md', 'csv', 'log'], true)) {
            try {
                return [
                    'text' => (string) Storage::disk($disk)->get($path),
                    'method' => 'text',
                    'warnings' => [],
                ];
            } catch (\Throwable $e) {
                return ['text' => '', 'method' => 'text', 'warnings' => ['Failed to read text: ' . $e->getMessage()]];
            }
        }

        // DOCX (Zip + document.xml)
        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $ext === 'docx') {
            if (!$fullPath) {
                return ['text' => '', 'method' => 'docx', 'warnings' => ['No local path available for DOCX extraction.']];
            }
            return $this->extractDocx($fullPath);
        }

        // PDF (best-effort: attempt pdftotext if available)
        if ($mimeType === 'application/pdf' || $ext === 'pdf') {
            if (!$fullPath) {
                return ['text' => '', 'method' => 'pdf', 'warnings' => ['No local path available for PDF extraction.']];
            }
            return $this->extractPdf($fullPath);
        }

        // Images (best-effort OCR via tesseract if available)
        if ($mimeType && str_starts_with($mimeType, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            if (!$fullPath) {
                return ['text' => '', 'method' => 'ocr', 'warnings' => ['No local path available for OCR.']];
            }
            return $this->ocrImage($fullPath);
        }

        $warnings[] = 'Unsupported file type for text extraction.';
        return ['text' => '', 'method' => 'unknown', 'warnings' => $warnings];
    }

    /**
     * @return array{text:string, method:string, warnings:array<int,string>}
     */
    protected function extractDocx(string $fullPath): array
    {
        $warnings = [];
        if (!class_exists(\ZipArchive::class)) {
            return ['text' => '', 'method' => 'docx', 'warnings' => ['ZipArchive extension not available.']];
        }

        $zip = new \ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return ['text' => '', 'method' => 'docx', 'warnings' => ['Unable to open DOCX.']];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || $xml === '') {
            return ['text' => '', 'method' => 'docx', 'warnings' => ['DOCX document.xml not found.']];
        }

        $text = strip_tags($xml);
        $text = preg_replace("/\\s+/", ' ', (string) $text);
        return ['text' => trim((string) $text), 'method' => 'docx', 'warnings' => $warnings];
    }

    /**
     * @return array{text:string, method:string, warnings:array<int,string>}
     */
    protected function extractPdf(string $fullPath): array
    {
        $warnings = [];

        // Try pdftotext if available (common on servers; optional).
        $process = new Process(['bash', '-lc', 'command -v pdftotext >/dev/null 2>&1 && pdftotext -layout ' . escapeshellarg($fullPath) . ' -']);
        $process->setTimeout(20);
        try {
            $process->run();
        } catch (\Throwable $e) {
            return ['text' => '', 'method' => 'pdf', 'warnings' => ['PDF extraction failed: ' . $e->getMessage()]];
        }

        if ($process->isSuccessful()) {
            $out = trim($process->getOutput());
            if ($out !== '') {
                return ['text' => $out, 'method' => 'pdftotext', 'warnings' => []];
            }
            $warnings[] = 'pdftotext returned empty output.';
        } else {
            $warnings[] = 'pdftotext not available or failed.';
        }

        return ['text' => '', 'method' => 'pdf', 'warnings' => $warnings];
    }

    /**
     * @return array{text:string, method:string, warnings:array<int,string>}
     */
    protected function ocrImage(string $fullPath): array
    {
        $warnings = [];

        $process = new Process(['bash', '-lc', 'command -v tesseract >/dev/null 2>&1 && tesseract ' . escapeshellarg($fullPath) . ' stdout -l eng 2>/dev/null']);
        $process->setTimeout(60);
        try {
            $process->run();
        } catch (\Throwable $e) {
            return ['text' => '', 'method' => 'ocr', 'warnings' => ['OCR failed: ' . $e->getMessage()]];
        }

        if ($process->isSuccessful()) {
            $out = trim($process->getOutput());
            if ($out !== '') {
                return ['text' => $out, 'method' => 'tesseract', 'warnings' => []];
            }
            $warnings[] = 'tesseract returned empty output.';
        } else {
            $warnings[] = 'tesseract not available or failed.';
        }

        return ['text' => '', 'method' => 'ocr', 'warnings' => $warnings];
    }
}

