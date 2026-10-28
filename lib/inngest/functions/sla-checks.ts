import { z } from "zod";
import { inngest } from "../client";
import {
  createJobClient,
  scheduledJobsEnabled,
  assertJobResult,
} from "../job-runtime";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplateWithClient } from "@/lib/email/templates";

const ticketSchema = z.object({
  id: z.string().uuid(),
  subject: z.string(),
  ticket_number: z.string(),
  priority: z.string(),
  created_at: z.string(),
  first_response_at: z.string().nullable(),
  sla_response_due_at: z.string().nullable(),
  sla_resolution_due_at: z.string().nullable(),
  sla_response_breached: z.boolean(),
  sla_resolution_breached: z.boolean(),
  sla_response_warned_at: z.string().nullable(),
  sla_resolution_warned_at: z.string().nullable(),
  sla_paused_duration_minutes: z.number(),
  assignee: z.object({ email: z.string().nullable() }).nullable(),
});

export const checkSLACompliance = inngest.createFunction(
  { id: "check-sla-compliance", name: "Check SLA Compliance", concurrency: 1 },
  { cron: "*/5 * * * *" },
  async ({ step }) => {
    if (!scheduledJobsEnabled())
      return { skipped: true, reason: "schedules_disabled" };
    const admin = createJobClient();
    const tickets = await step.run("find-active-tickets", async () => {
      const { data, error } = await admin
        .from("support_tickets")
        .select("*, assignee:users!assigned_to(email)")
        .in("status", [
          "open",
          "in_progress",
          "waiting_on_client",
          "waiting_on_third_party",
        ])
        .eq("sla_paused", false)
        .is("deleted_at", null);
      assertJobResult(error, "Read support SLA deadlines");
      return z.array(ticketSchema).parse(data ?? []);
    });
    let warnings = 0;
    let breaches = 0;
    for (const ticket of tickets) {
      for (const kind of ["response", "resolution"] as const) {
        if (kind === "response" && ticket.first_response_at) continue;
        if (ticket[`sla_${kind}_breached`]) continue;
        const due = ticket[`sla_${kind}_due_at`];
        if (!due) continue;
        const dueAt =
          new Date(due).getTime() + ticket.sla_paused_duration_minutes * 60000;
        const createdAt = new Date(ticket.created_at).getTime();
        const now = Date.now();
        if (!Number.isFinite(dueAt) || dueAt <= createdAt) {
          throw new Error("Invalid support SLA deadline");
        }
        const breached = now >= dueAt;
        const warning =
          !breached && now >= createdAt + (dueAt - createdAt) * 0.75;
        if (!breached && (!warning || ticket[`sla_${kind}_warned_at`]))
          continue;
        const outcome = await step.run(
          `notify-${kind}-${ticket.id}`,
          async () => {
            if (ticket.assignee?.email) {
              const rendered = await renderEmailTemplateWithClient(
                admin,
                breached ? "sla_breach" : "sla_warning",
                {
                  request_title: ticket.subject,
                  request_number: ticket.ticket_number,
                  request_priority: ticket.priority,
                  time_remaining_hours: Math.max(
                    0,
                    (dueAt - now) / 3600000,
                  ).toFixed(1),
                  response_time_hours:
                    kind === "response" ? (dueAt - createdAt) / 3600000 : 0,
                  resolution_time_hours:
                    kind === "resolution" ? (dueAt - createdAt) / 3600000 : 0,
                  request_url: `${process.env.NEXT_PUBLIC_APP_URL}/support/${ticket.id}`,
                },
              );
              if (!rendered)
                throw new Error("Support SLA email template unavailable");
              const sent = await sendEmail({
                to: ticket.assignee.email,
                subject: rendered.subject,
                html: rendered.html,
                text: rendered.plainText,
              });
              if (!sent.success)
                throw new Error("Support SLA email delivery failed");
            } else if (!breached) {
              return "unassigned";
            }
            const patch = breached
              ? {
                  [`sla_${kind}_breached`]: true,
                  [`sla_${kind}_breached_at`]: new Date(now).toISOString(),
                }
              : { [`sla_${kind}_warned_at`]: new Date(now).toISOString() };
            const { error } = await admin
              .from("support_tickets")
              .update(patch)
              .eq("id", ticket.id);
            assertJobResult(error, "Save support SLA notification");
            return breached ? "breach" : "warning";
          },
        );
        if (outcome === "breach") breaches++;
        if (outcome === "warning") warnings++;
      }
    }
    return { checked: tickets.length, warnings, breaches };
  },
);
