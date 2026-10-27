# OpenShip release checklist

Use [the deployment guide](docs/DEPLOYMENT.md) as the source of truth.

- [ ] TypeScript, lint, unit and relevant provider tests complete.
- [ ] Linux amd64 image built off-host with public build settings only.
- [ ] Database and runtime configuration backed up before migrations.
- [ ] Dedicated database, authentication and canonical application URLs verified.
- [ ] Private candidate healthy with required networking, limits and secrets.
- [ ] Public registration disabled; administrator client provisioning verified.
- [ ] Relevant Stripe, Microsoft email/calendar and background-job checks complete.
- [ ] Candidate promoted; public health, revision, TLS and tenant boundaries checked.
- [ ] OpenShip service runtime settings and protected environment file agree.
- [ ] Previous container retained for rollback; evidence recorded in docs/qa.
