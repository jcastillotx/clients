<?php

namespace Tests\Unit\Ai;

use App\Services\AI\DocumentAnalysisService;
use PHPUnit\Framework\TestCase;

class DocumentAnalysisServiceDetectionTest extends TestCase
{
    public function test_detect_document_type_contract(): void
    {
        $svc = new DocumentAnalysisService(
            providers: $this->createStub(\App\Services\AI\AIProviderManager::class),
            extractor: $this->createStub(\App\Services\AI\DocumentTextExtractor::class)
        );

        $type = $svc->detectDocumentType('THIS AGREEMENT is made between the parties. Term and termination apply.', 'my-contract.pdf', 'application/pdf');
        $this->assertSame('contract', $type);
    }

    public function test_detect_document_type_invoice(): void
    {
        $svc = new DocumentAnalysisService(
            providers: $this->createStub(\App\Services\AI\AIProviderManager::class),
            extractor: $this->createStub(\App\Services\AI\DocumentTextExtractor::class)
        );

        $type = $svc->detectDocumentType("Invoice #1001\nSubtotal 100\nTotal 108", 'invoice-1001.pdf', 'application/pdf');
        $this->assertSame('invoice', $type);
    }

    public function test_detect_document_type_technical(): void
    {
        $svc = new DocumentAnalysisService(
            providers: $this->createStub(\App\Services\AI\AIProviderManager::class),
            extractor: $this->createStub(\App\Services\AI\DocumentTextExtractor::class)
        );

        $type = $svc->detectDocumentType('Technical requirements: API endpoints, dependencies, framework choices.', 'spec.txt', 'text/plain');
        $this->assertSame('technical', $type);
    }
}
