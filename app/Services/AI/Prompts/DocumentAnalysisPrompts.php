<?php

namespace App\Services\AI\Prompts;

use App\Models\Document;

class DocumentAnalysisPrompts
{
    public static function contractSystem(): string
    {
        return <<<'SYS'
You are a contract review assistant. Analyze the provided contract text and return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "plain_english_summary": "string",
  "parties": ["string", ...],
  "key_dates": {"effective_date":"string|null","start_date":"string|null","end_date":"string|null","renewal":"string|null"},
  "payment_terms": {"amounts":"string|null","schedule":"string|null","late_fees":"string|null"},
  "obligations": {"client":["string",...],"provider":["string",...]},
  "unusual_clauses": ["string", ...],
  "missing_sections": ["string", ...],
  "risk_assessment": {"level":"low|medium|high","reasons":["string",...]},
  "suggested_modifications": ["string", ...]
}

Rules:
- If the full text is unavailable, explain limitations and use the checklist to infer missing sections.
- Be conservative: flag unknowns explicitly.
SYS;
    }

    public static function defaultContractChecklist(): string
    {
        return implode("\n", [
            'Standard sections checklist:',
            '- Parties / Definitions',
            '- Scope of work',
            '- Term and termination (including cancellation)',
            '- Payment terms and invoicing',
            '- Confidentiality',
            '- IP ownership / licensing',
            '- Warranties / disclaimers',
            '- Limitation of liability',
            '- Indemnification',
            '- Governing law',
            '- Dispute resolution',
            '- Change control',
            '- Acceptance criteria',
            '- Data protection / security (if applicable)',
        ]);
    }

    /**
     * @param array{text:string,method:string,warnings:array<int,string>} $extraction
     */
    public static function contractUser(Document $doc, string $text, string $standardChecklist, array $extraction): string
    {
        $meta = json_encode([
            'document_id' => $doc->id,
            'filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'extraction_method' => $extraction['method'] ?? null,
            'extraction_warnings' => $extraction['warnings'] ?? [],
        ], JSON_UNESCAPED_SLASHES);

        $body = $text !== '' ? $text : '(No text could be extracted from this file.)';

        return <<<USR
Document metadata:
{$meta}

Compare against this checklist:
{$standardChecklist}

Contract text:
{$body}
USR;
    }

    public static function invoiceSystem(): string
    {
        return <<<'SYS'
You are an invoice analysis assistant. Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "invoice_number":"string|null",
  "vendor":"string|null",
  "customer":"string|null",
  "invoice_date":"string|null",
  "due_date":"string|null",
  "currency":"string|null",
  "line_items":[{"description":"string","quantity":"number|null","unit_price":"number|null","amount":"number|null"}],
  "totals":{"subtotal":"number|null","tax":"number|null","discount":"number|null","total":"number|null"},
  "calculation_check":{"matches":"boolean|null","issues":["string",...]},
  "scope_comparison":{"matches_estimate":"boolean|null","discrepancies":["string",...]},
  "summary":"string"
}

Rules:
- If amounts are unclear, set null and add issue notes.
SYS;
    }

    /**
     * @param array<string,mixed>|null $estimateData
     * @param array{text:string,method:string,warnings:array<int,string>} $extraction
     */
    public static function invoiceUser(Document $doc, string $text, ?array $estimateData, array $extraction): string
    {
        $meta = json_encode([
            'document_id' => $doc->id,
            'filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'extraction_method' => $extraction['method'] ?? null,
            'extraction_warnings' => $extraction['warnings'] ?? [],
        ], JSON_UNESCAPED_SLASHES);

        $estimate = $estimateData ? json_encode($estimateData, JSON_UNESCAPED_SLASHES) : 'null';
        $body = $text !== '' ? $text : '(No text could be extracted from this file.)';

        return <<<USR
Document metadata:
{$meta}

Related project estimate (may be null):
{$estimate}

Invoice text:
{$body}
USR;
    }

    public static function technicalSystem(): string
    {
        return <<<'SYS'
You are a technical analysis assistant. Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "summary":"string",
  "requirements":["string",...],
  "technologies":["string",...],
  "dependencies":["string",...],
  "implementation_notes":["string",...],
  "potential_challenges":["string",...]
}
SYS;
    }

    /**
     * @param array{text:string,method:string,warnings:array<int,string>} $extraction
     */
    public static function technicalUser(Document $doc, string $text, array $extraction): string
    {
        $meta = json_encode([
            'document_id' => $doc->id,
            'filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'extraction_method' => $extraction['method'] ?? null,
            'extraction_warnings' => $extraction['warnings'] ?? [],
        ], JSON_UNESCAPED_SLASHES);

        $body = $text !== '' ? $text : '(No text could be extracted from this file.)';

        return <<<USR
Document metadata:
{$meta}

Technical document text:
{$body}
USR;
    }

    public static function summarySystem(string $language): string
    {
        $language = trim($language) ?: 'en';
        return <<<SYS
You are a document summarization assistant. Output must be in language: {$language}.
Return ONLY valid JSON. No markdown. No extra keys.

Schema:
{
  "executive_summary":"string",
  "highlights":["string",...],
  "action_items":["string",...]
}
SYS;
    }

    /**
     * @param array{text:string,method:string,warnings:array<int,string>} $extraction
     */
    public static function summaryUser(Document $doc, string $text, array $extraction): string
    {
        $meta = json_encode([
            'document_id' => $doc->id,
            'filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'extraction_method' => $extraction['method'] ?? null,
            'extraction_warnings' => $extraction['warnings'] ?? [],
        ], JSON_UNESCAPED_SLASHES);

        $body = $text !== '' ? $text : '(No text could be extracted from this file.)';

        return <<<USR
Document metadata:
{$meta}

Document text:
{$body}
USR;
    }
}

