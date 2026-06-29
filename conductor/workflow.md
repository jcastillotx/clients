# Workflow

## TDD Policy

**Strict TDD** — tests must be written before implementation.

1. Write a failing test (RED)
2. Run the test — it must fail
3. Write the minimum code to pass (GREEN)
4. Run the test — it must pass
5. Refactor while keeping tests green (IMPROVE)
6. Verify coverage ≥ 80%

Use the `tdd-guide` agent proactively for all new features and bug fixes.

**Test types required:**
- Unit tests — individual functions, utilities, hooks
- Integration tests — API routes, database operations
- E2E tests — critical user flows (Playwright)

## Commit Strategy

**Conventional Commits** format is required:

```
<type>: <description>

<optional body>
```

Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`

Examples:
- `feat: add project milestone tracking`
- `fix: resolve invoice PDF generation on Safari`
- `refactor: extract client activity logger`

## Code Review Policy

**Required for all non-trivial changes.** Use the `code-reviewer` agent immediately after writing or modifying code.

- Address all CRITICAL and HIGH severity findings before proceeding
- Fix MEDIUM severity issues when feasible
- Document any accepted LOW findings in the PR description

## Verification Checkpoints

**Manual verification required after each phase completion.** Do not advance to the next phase of a track until the current phase is verified.

Verification includes:
- Run `pnpm type-check` — zero errors
- Run `pnpm test:run` — all tests pass
- Smoke-test the affected UI flows in browser
- Confirm no regressions in adjacent features

## Task Lifecycle

```
defined → in_progress → review → verified → complete
```

- **defined**: Task scoped and documented in track file
- **in_progress**: Active development (TDD workflow active)
- **review**: Code review agent run, issues addressed
- **verified**: Phase verification checkpoint passed
- **complete**: Merged, tests green, no regressions

## Agent Routing

| Task type | Agents to use |
|-----------|--------------|
| New feature | planner → tdd-guide → code-reviewer |
| Bug fix | tdd-guide → code-reviewer |
| Refactor | planner → code-reviewer → refactor-cleaner |
| DB schema | database-reviewer |
| Security concern | security-reviewer |
| Build failure | build-error-resolver |
| E2E tests | e2e-runner |
