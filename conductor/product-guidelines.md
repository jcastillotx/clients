# Product Guidelines

## Voice and Tone

**Professional and direct.** Clear, agency-grade language without fluff.

- Use plain language — no jargon unless it's domain-standard (e.g. "invoice", "proposal", "retainer")
- Be concise — agency owners are busy; every word must earn its place
- Active voice preferred — "Create an invoice" not "An invoice can be created"
- Error messages should be actionable — tell users what to do, not just what went wrong

## Design Principles

1. **Simplicity over features** — when in doubt, do less and do it well
2. **Performance first** — agencies rely on this daily; slow is broken
3. **Reliability over novelty** — choose proven patterns over cutting-edge experiments
4. **Data isolation by default** — multi-tenant RLS ensures no client ever sees another's data
5. **Type safety throughout** — TypeScript strict mode, Zod at boundaries, no `any`

## UI Standards

- Use Server Components by default; Client Components only when interactivity is required
- Follow shadcn/ui conventions — don't invent custom component APIs
- Accessibility: semantic HTML, ARIA labels, keyboard-navigable flows
- Mobile-responsive — agency staff work from phones and tablets
- Dark mode supported via `next-themes`

## Feature Flag Policy

All features are gated via the 4-level flag system (User > Role > Client > Global). New features ship disabled by default and are enabled progressively. See `lib/db/schema/feature-flags.ts` for implementation.
