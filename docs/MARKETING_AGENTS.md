# Marketing Agents

The Marketing Agents workspace runs client-scoped, approval-safe AI workflows from `/marketing/agents`.

## Initial workflows

- **Generate Campaign Plan** runs a Campaign Strategist followed by a Brand Guardian. When draft creation is enabled, the result is saved as a campaign with `draft` status.
- **Generate Content Calendar** runs a Content Creator followed by a Brand Guardian. Generated calendar records are saved as `pending_approval`.
- **Run Quality Check** is deterministic and works without an AI provider. It checks prohibited brand language, unsupported numeric claims, readability, and excessive formatting.

All workflows load the selected client's latest brand guide. Client identifiers are assigned and validated server-side and are never selected by an AI model.

## Runtime

1. `POST /api/marketing/agents` authenticates staff, validates the selected client and input, then creates an `ai_tasks` record.
2. The route sends a `marketing-agent/run.requested` event to Inngest.
3. The worker executes each agent stage, records the trace and AI usage, and saves approval-safe records when requested.
4. The UI polls `GET /api/marketing/agents/[id]` until the run completes or fails.

OpenAI credentials are resolved in this order:

1. The client's encrypted `openai/api_key` integration setting.
2. The platform `OPENAI_API_KEY` environment variable.

`MARKETING_AGENT_MODEL` selects the model. Optional per-million-token pricing environment variables provide estimated cost reporting; leaving them at zero records tokens without inventing a monetary estimate.

## Safety boundaries

- Agent runs are staff-only in the initial release.
- Agent starts are limited per staff user to control accidental provider spend.
- Generated material never publishes automatically.
- Campaigns remain drafts.
- Calendar content requires human approval.
- Agent prompts treat client context and user input as untrusted data.
- Provider credentials are never added to prompts or logs.
- Every run, model, token count, estimated cost and generated record remains linked to the selected client.

## Production requirements

- Configure Inngest (`INNGEST_EVENT_KEY` and `INNGEST_SIGNING_KEY`).
- Configure either a platform OpenAI key or a per-client encrypted OpenAI integration.
- Set `MARKETING_AGENT_MODEL` to a JSON-output-capable model.
- Verify the existing RLS policies and background-job endpoint after deployment.
