<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\OnboardingTask;
use App\Models\OnboardingWorkflow;
use App\Models\Questionnaire;
use App\Services\FormTemplateService;
use Illuminate\Support\Arr;

class OnboardingService
{
    public function __construct(private readonly BrandGuideBuilderService $brandGuides) {}

    /**
     * Create a default onboarding workflow + checklist for a client.
     */
    public function createOnboardingWorkflow(Client $client): OnboardingWorkflow
    {
        $workflow = OnboardingWorkflow::firstOrCreate(
            ['client_id' => $client->id, 'status' => 'in_progress'],
            [
                'current_step' => 1,
                'total_steps' => 1,
                'completion_percentage' => 0,
                'started_at' => now(),
            ]
        );

        // Idempotent task creation by task_name.
        $this->ensureTask($workflow, 'Welcome email sent', 'checklist');
        $this->ensureTask($workflow, 'Contract signed', 'contract');
        $this->ensureTask($workflow, 'Payment method added', 'billing');
        $this->ensureTask($workflow, 'Brand questionnaire completed', 'questionnaire', [
            'questionnaire_type' => 'brand_discovery',
        ]);
        $this->ensureTask($workflow, 'Access credentials provided', 'setup');
        $this->ensureTask($workflow, 'Analytics connected', 'integration');
        $this->ensureTask($workflow, 'Social accounts linked', 'integration');
        $this->ensureTask($workflow, 'Kickoff meeting scheduled', 'meeting');
        $this->ensureTask($workflow, 'Strategy document delivered', 'deliverable');

        $workflow->recalcProgress();

        // Ensure brand discovery questionnaire exists
        $this->brandDiscoveryQuestionnaire($client);

        return $workflow->fresh();
    }

    /**
     * Returns (and creates if missing) the brand discovery questionnaire scaffold.
     */
    public function brandDiscoveryQuestionnaire(Client $client): Questionnaire
    {
        // Get questions from form template (admin-configurable)
        $formTemplateService = app(FormTemplateService::class);
        $questions = $formTemplateService->getFields('onboarding');

        // Fallback to defaults if template is empty
        if (empty($questions)) {
            $questions = FormTemplateService::$defaults['onboarding']['fields'] ?? [];
        }

        return Questionnaire::firstOrCreate(
            [
                'client_id' => $client->id,
                'questionnaire_type' => 'brand_discovery',
            ],
            [
                'title' => 'Brand Discovery Questionnaire',
                'questions' => $questions,
                'answers' => null,
                'status' => 'draft',
                'assigned_to' => null,
                'due_date' => now()->addDays(7)->toDateString(),
            ]
        );
    }

    /**
     * Auto-populate artifacts from a submitted questionnaire.
     *
     * Current behavior:
     * - Generate a draft Brand Guide (best-effort) using existing BrandGuideBuilderService.
     */
    public function applyQuestionnaireAnswersToArtifacts(Questionnaire $questionnaire, array $options = []): array
    {
        $questionnaire->loadMissing('client');
        $client = $questionnaire->client;

        if (! $client) {
            return ['ok' => false, 'error' => 'Questionnaire missing client.'];
        }

        $answers = (array) ($questionnaire->answers ?? []);

        // Store raw onboarding answers on BrandGuide meta as seed context
        $res = $this->brandGuides->generateBrandGuide($client, [
            'created_by' => $options['created_by'] ?? null,
            'is_public' => false,
        ]);

        $guide = $res['guide'];
        $guide->update([
            'meta' => array_merge((array) ($guide->meta ?? []), [
                'onboarding' => [
                    'questionnaire_id' => $questionnaire->id,
                    'questionnaire_type' => $questionnaire->questionnaire_type,
                    'answers' => $answers,
                ],
            ]),
        ]);

        return [
            'ok' => true,
            'brand_guide_id' => $guide->id,
        ];
    }

    private function ensureTask(OnboardingWorkflow $workflow, string $name, string $type, array $meta = []): OnboardingTask
    {
        return OnboardingTask::firstOrCreate(
            [
                'onboarding_workflow_id' => $workflow->id,
                'task_name' => $name,
            ],
            [
                'task_type' => $type,
                'status' => 'pending',
                'due_date' => Arr::get($meta, 'due_date'),
                'meta' => $meta ?: null,
            ]
        );
    }
}
