# Isolated client-portal staging

This is a dedicated local environment, separate from production and the existing `client-portal` development stack. It uses a schema-only baseline, synthetic tenants/accounts, independent volumes, rotated JWT/API/database credentials, private captured mail, and local provider simulators. No production records or provider secrets are copied.

| Service | URL / port |
| --- | --- |
| Portal | http://127.0.0.1:3100 |
| Supabase API/Auth | http://127.0.0.1:55321 |
| PostgreSQL | 127.0.0.1:55322 |
| Studio | http://127.0.0.1:55323 |
| Captured mail | http://127.0.0.1:55324 |
| SMTP capture | 127.0.0.1:55325 |
| S3 simulator (Moto) | http://127.0.0.1:59000 |
| Stripe API simulator | http://127.0.0.1:12111 |
| Inngest dev server | http://127.0.0.1:8288 |

## Provision and run

Requires Node >=20, pnpm, Python, Docker/OrbStack, and the Supabase CLI. From the repository root:

```sh
pnpm install --frozen-lockfile
python3 staging/scripts/setup.py
python3 -m venv staging/.venv
staging/.venv/bin/pip install -r staging/requirements.txt
node staging/scripts/seed.mjs
```

Start each long-running service in its own terminal:

```sh
staging/.venv/bin/moto_server -H 127.0.0.1 -p 59000
pnpm dlx inngest-cli dev --host 127.0.0.1 --port 8288 --no-discovery
python3 staging/scripts/run.py
```

Create the local synthetic bucket with `staging/.venv/bin/python staging/scripts/bucket.py`. Register background functions with `PUT http://127.0.0.1:3100/api/inngest`.

Run the persisted workflow checks:

```sh
node staging/scripts/workflows.mjs
```

Results are written after every check to `staging/artifacts/workflows.json`. Account details are in the ignored, permission-restricted `staging/.accounts.json`; configuration is in ignored `staging/.env.staging`. Never commit or share either file. The staging directory is excluded from application Docker build contexts.

`setup.py` preserves an already-provisioned staging stack. Do not use `supabase stop/start` directly after key rotation: the CLI defaults would replace its isolated credentials. Start/stop the named `*_clients-isolated-qa` containers, leaving other projects untouched. This bootstrap targets macOS OrbStack's Docker socket.

The runtime refuses external database/auth endpoints, live Stripe credentials, and external email/AI/job credentials when `STAGING_ISOLATED=1`. SMTP is configured in the synthetic database to the local capture service.

## Evidence and limits

The persisted matrix covers authorization forgery, tenant isolation, account lifecycle boundaries, request/support/project workflows, storage bytes and versions, messaging, welcome mail, proposal signatures, subscriptions, payments/webhook replay, password recovery, MFA and profile images. Simulator results prove application behavior against controlled protocols. They do not prove settlement, real provider deliverability, or Google/Microsoft OAuth consent and integration behavior; dedicated provider sandbox accounts are required for those acceptance gates.

## Client marketing tools

The client tool library includes 48 marketing skills from the supplied MIT-licensed archive; eight repository administration skills are excluded. Start `node staging/scripts/marketing-ai-simulator.mjs` and set `MARKETING_AI_SIMULATOR_URL=http://127.0.0.1:3102/v1/chat/completions` in the ignored staging environment, then restart the app. This endpoint is accepted only in isolated mode and must use localhost; production ignores it. Never put real AI credentials into isolated staging.

Run `node staging/scripts/marketing-tools.mjs` for 59 checks: all 48 tools through client-authenticated HTTP calls, persisted structured drafts, history, tenant forgery, disabled-feature enforcement, validation, malformed AI output, and provider failure. It applies migrations 039/040 only to the dedicated local staging database. Simulator outputs are labeled fixtures and do not prove live model quality or provider-account access. The original `workflows.mjs` also verifies avatar removal clears auth metadata.
