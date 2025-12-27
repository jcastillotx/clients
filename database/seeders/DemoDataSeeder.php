<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Request;
use App\Models\RequestComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoClient = Client::where('email', 'demo@example.com')->first();

        if (! $demoClient) {
            return;
        }

        $clientUser = User::where('client_id', $demoClient->id)->first();
        $staffUser = User::where('email', 'staff@kre8ivdesigns.com')->first();

        // Create sample requests
        $requests = [
            [
                'title' => 'Website Redesign Project',
                'description' => 'We need a complete redesign of our company website. The current site is outdated and doesn\'t reflect our brand properly. We\'re looking for a modern, responsive design with improved user experience.',
                'type' => 'web_development',
                'status' => 'in_progress',
                'priority' => 'high',
                'assigned_to' => $staffUser?->id,
                'due_date' => now()->addDays(30),
                'started_at' => now()->subDays(5),
            ],
            [
                'title' => 'Logo Update',
                'description' => 'We need to refresh our logo to make it more modern while keeping the core elements recognizable.',
                'type' => 'graphic_design',
                'status' => 'completed',
                'priority' => 'medium',
                'assigned_to' => $staffUser?->id,
                'completed_at' => now()->subDays(10),
            ],
            [
                'title' => 'Social Media Campaign',
                'description' => 'Planning a Q1 social media campaign for our new product launch. Need content strategy, graphics, and scheduling.',
                'type' => 'social_media',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(14),
            ],
            [
                'title' => 'SEO Optimization',
                'description' => 'Our website needs SEO improvements. Currently not ranking well for our target keywords.',
                'type' => 'seo',
                'status' => 'in_review',
                'priority' => 'high',
            ],
        ];

        foreach ($requests as $requestData) {
            $request = Request::create([
                'client_id' => $demoClient->id,
                'created_by' => $clientUser?->id,
                ...$requestData,
            ]);

            // Add sample comments
            if ($staffUser && $clientUser) {
                RequestComment::create([
                    'request_id' => $request->id,
                    'user_id' => $staffUser->id,
                    'comment' => 'Thank you for your request! We\'ve reviewed the requirements and will begin work shortly.',
                    'is_internal' => false,
                ]);

                RequestComment::create([
                    'request_id' => $request->id,
                    'user_id' => $clientUser->id,
                    'comment' => 'Great, looking forward to seeing the progress!',
                    'is_internal' => false,
                ]);
            }
        }

        // Create sample contracts
        $contracts = [
            [
                'title' => 'Website Development Agreement',
                'description' => 'Service agreement for website development and ongoing maintenance.',
                'contract_number' => Contract::generateContractNumber(),
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addMonths(6),
                'value' => 15000.00,
                'status' => 'active',
                'signed_at' => now()->subMonths(6),
                'signed_by' => 'John Demo',
            ],
            [
                'title' => 'Monthly Marketing Services',
                'description' => 'Retainer agreement for ongoing digital marketing services.',
                'contract_number' => Contract::generateContractNumber(),
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'value' => 24000.00,
                'status' => 'pending_signature',
            ],
        ];

        foreach ($contracts as $contractData) {
            Contract::create([
                'client_id' => $demoClient->id,
                ...$contractData,
            ]);
        }

        // Create sample invoices
        $invoices = [
            [
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal' => 2500.00,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount' => 0,
                'amount' => 2500.00,
                'issue_date' => now()->subDays(30),
                'due_date' => now(),
                'status' => 'sent',
                'items' => [
                    ['description' => 'Website Design - Homepage', 'quantity' => 1, 'unit_price' => 1500],
                    ['description' => 'Website Design - Inner Pages (5)', 'quantity' => 5, 'unit_price' => 200],
                ],
            ],
            [
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal' => 1200.00,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount' => 0,
                'amount' => 1200.00,
                'issue_date' => now()->subDays(60),
                'due_date' => now()->subDays(30),
                'paid_at' => now()->subDays(25),
                'status' => 'paid',
                'items' => [
                    ['description' => 'Logo Design', 'quantity' => 1, 'unit_price' => 800],
                    ['description' => 'Brand Guidelines Document', 'quantity' => 1, 'unit_price' => 400],
                ],
            ],
            [
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal' => 500.00,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount' => 0,
                'amount' => 500.00,
                'issue_date' => now()->subDays(45),
                'due_date' => now()->subDays(15),
                'status' => 'overdue',
                'items' => [
                    ['description' => 'Monthly SEO Services - November', 'quantity' => 1, 'unit_price' => 500],
                ],
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $items = $invoiceData['items'];
            unset($invoiceData['items']);

            $invoice = Invoice::create([
                'client_id' => $demoClient->id,
                ...$invoiceData,
            ]);

            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'sort_order' => $index,
                ]);
            }
        }

        // Create sample documents
        $documents = [
            [
                'title' => 'Brand Guidelines',
                'description' => 'Complete brand identity guidelines document.',
                'filename' => 'brand-guidelines.pdf',
                'original_filename' => 'Brand_Guidelines_2024.pdf',
                'file_path' => 'sample/brand-guidelines.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048000,
                'category' => 'design',
            ],
            [
                'title' => 'Website Proposal',
                'description' => 'Proposal document for website redesign project.',
                'filename' => 'website-proposal.pdf',
                'original_filename' => 'Website_Redesign_Proposal.pdf',
                'file_path' => 'sample/website-proposal.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024000,
                'category' => 'proposal',
            ],
            [
                'title' => 'Monthly Report - November',
                'description' => 'SEO and marketing performance report for November.',
                'filename' => 'monthly-report-nov.pdf',
                'original_filename' => 'Monthly_Report_November.pdf',
                'file_path' => 'sample/monthly-report-nov.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 512000,
                'category' => 'report',
            ],
        ];

        foreach ($documents as $documentData) {
            Document::create([
                'client_id' => $demoClient->id,
                'uploaded_by' => $staffUser?->id,
                ...$documentData,
            ]);
        }
    }
}
