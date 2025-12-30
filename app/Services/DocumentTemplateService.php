<?php

namespace App\Services;

use App\Models\DocumentTemplate;

class DocumentTemplateService
{
    /**
     * @var array<int, array{name:string, category:string, variables:array<int,string>, body:string}>
     */
    public static array $defaults = [
        [
            'name' => 'Mutual Non-Disclosure Agreement (NDA)',
            'category' => 'nda',
            'variables' => ['company_name', 'client_name', 'effective_date', 'term_length', 'disclosing_party', 'receiving_party'],
            'body' => <<<HTML
<h1>Mutual Non-Disclosure Agreement</h1>
<p>This Mutual NDA (the "Agreement") is effective {{effective_date}} between {{company_name}} and {{client_name}}.</p>
<h2>1. Purpose</h2>
<p>The parties wish to explore a business relationship and may disclose confidential information to one another.</p>
<h2>2. Confidential Information</h2>
<p>"Confidential Information" means any non-public business, technical, or financial information disclosed by a party.</p>
<h2>3. Obligations</h2>
<ul>
    <li>Use Confidential Information solely for the purpose above.</li>
    <li>Protect Confidential Information with reasonable care.</li>
    <li>Disclose only to representatives bound by confidentiality.</li>
</ul>
<h2>4. Term</h2>
<p>This Agreement remains in effect for {{term_length}} from the Effective Date.</p>
<h2>5. Return of Materials</h2>
<p>Upon request, each party will return or destroy Confidential Information.</p>
<p>Disclosing Party: {{disclosing_party}}</p>
<p>Receiving Party: {{receiving_party}}</p>
HTML,
        ],
        [
            'name' => 'Master Services Agreement (MSA)',
            'category' => 'msa',
            'variables' => ['company_name', 'client_name', 'effective_date', 'payment_terms', 'governing_law'],
            'body' => <<<HTML
<h1>Master Services Agreement</h1>
<p>This MSA is made effective {{effective_date}} between {{company_name}} ("Service Provider") and {{client_name}} ("Client").</p>
<h2>1. Services</h2>
<p>Services will be described in Statements of Work (SOWs) and governed by this Agreement.</p>
<h2>2. Fees &amp; Payment</h2>
<p>Client will pay fees according to the applicable SOW. Payment terms: {{payment_terms}}.</p>
<h2>3. Intellectual Property</h2>
<p>Each party retains ownership of its pre-existing IP. Deliverables are governed by the SOW.</p>
<h2>4. Confidentiality</h2>
<p>Each party agrees to protect the other party's Confidential Information.</p>
<h2>5. Governing Law</h2>
<p>This Agreement is governed by the laws of {{governing_law}}.</p>
HTML,
        ],
        [
            'name' => 'Data Processing Agreement (DPA)',
            'category' => 'dpa',
            'variables' => ['company_name', 'client_name', 'effective_date', 'processing_purpose', 'subprocessors'],
            'body' => <<<HTML
<h1>Data Processing Agreement</h1>
<p>This DPA is effective {{effective_date}} between {{company_name}} ("Processor") and {{client_name}} ("Controller").</p>
<h2>1. Processing</h2>
<p>Processor will process personal data solely for {{processing_purpose}} and in accordance with this Agreement.</p>
<h2>2. Security</h2>
<p>Processor will implement appropriate technical and organizational measures to protect personal data.</p>
<h2>3. Subprocessors</h2>
<p>Approved subprocessors: {{subprocessors}}.</p>
<h2>4. Data Subject Rights</h2>
<p>Processor will assist Controller in responding to data subject requests.</p>
<h2>5. Return/Deletion</h2>
<p>Upon termination, Processor will delete or return personal data as instructed.</p>
HTML,
        ],
        [
            'name' => 'Statement of Work (SOW)',
            'category' => 'sow',
            'variables' => ['company_name', 'client_name', 'project_name', 'start_date', 'end_date', 'deliverables', 'fees'],
            'body' => <<<HTML
<h1>Statement of Work</h1>
<p>Project: {{project_name}}</p>
<p>Client: {{client_name}}</p>
<p>Provider: {{company_name}}</p>
<h2>1. Scope &amp; Deliverables</h2>
<p>{{deliverables}}</p>
<h2>2. Timeline</h2>
<p>Start Date: {{start_date}}<br>End Date: {{end_date}}</p>
<h2>3. Fees</h2>
<p>{{fees}}</p>
<h2>4. Acceptance</h2>
<p>Deliverables will be deemed accepted unless Client notifies Provider of issues within 5 business days.</p>
HTML,
        ],
        [
            'name' => 'Creative Brief',
            'category' => 'creative',
            'variables' => ['company_name', 'client_name', 'project_name', 'objective', 'audience', 'tone', 'deliverables', 'deadline'],
            'body' => <<<HTML
<h1>Creative Brief</h1>
<p>Project: {{project_name}}</p>
<p>Client: {{client_name}}</p>
<p>Prepared by: {{company_name}}</p>
<h2>Objective</h2>
<p>{{objective}}</p>
<h2>Target Audience</h2>
<p>{{audience}}</p>
<h2>Tone &amp; Voice</h2>
<p>{{tone}}</p>
<h2>Deliverables</h2>
<p>{{deliverables}}</p>
<h2>Deadline</h2>
<p>{{deadline}}</p>
HTML,
        ],
    ];

    public function seedDefaults(): void
    {
        foreach (self::$defaults as $definition) {
            DocumentTemplate::firstOrCreate(
                ['name' => $definition['name'], 'category' => $definition['category']],
                [
                    'body' => $definition['body'],
                    'variables' => $definition['variables'],
                ]
            );
        }
    }
}
