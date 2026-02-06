import { inngest } from "../client";
import { createClient } from "@/lib/supabase/server";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplate, type SLABreachData } from "@/lib/email/templates";

// Check SLA compliance every 5 minutes
export const checkSLACompliance = inngest.createFunction(
  {
    id: "check-sla-compliance",
    name: "Check SLA Compliance",
  },
  { cron: "*/5 * * * *" }, // Every 5 minutes
  async ({ step }) => {
    const supabase = createClient();

    // Step 1: Find active requests that need SLA monitoring
    const activeRequests = await step.run("find-active-requests", async () => {
      const { data } = await supabase
        .from("requests")
        .select(
          `
          *,
          client:clients(
            id,
            company_name,
            email,
            sla_response_time_hours,
            sla_resolution_time_hours
          ),
          assigned_to:users!assigned_to(id, name, email)
        `,
        )
        .in("status", ["pending", "in_progress"])
        .is("deleted_at", null);

      return data || [];
    });

    const warnings = [];
    const breaches = [];

    // Step 2: Check each request for SLA compliance
    for (const request of activeRequests) {
      await step.run(`check-sla-${request.id}`, async () => {
        if (!request.client) return;

        const now = new Date();
        const createdAt = new Date(request.created_at);
        const hoursElapsed = (now.getTime() - createdAt.getTime()) / (1000 * 60 * 60);

        const responseTime = request.client.sla_response_time_hours || 24;
        const resolutionTime = request.client.sla_resolution_time_hours || 72;

        // Check response SLA
        if (!request.first_response_at && hoursElapsed >= responseTime) {
          // SLA breach - no response yet
          breaches.push({
            request,
            type: "response",
            hoursOverdue: hoursElapsed - responseTime,
          });

          // Update request to mark SLA breach
          await supabase
            .from("requests")
            .update({
              sla_breached: true,
              sla_breach_type: "response",
              sla_breach_at: now.toISOString(),
            })
            .eq("id", request.id);

          // Send breach notification
          if (request.assigned_to?.email) {
            const templateData: SLABreachData = {
              request: {
                title: request.title,
                request_number: request.request_number,
                priority: request.priority,
              },
              sla: {
                response_time_hours: responseTime,
                resolution_time_hours: resolutionTime,
              },
              time_remaining_hours: 0,
              request_url: `${process.env.NEXT_PUBLIC_APP_URL}/requests/${request.id}`,
            };

            const rendered = await renderEmailTemplate("sla_breach", templateData);
            if (rendered) {
              await sendEmail({
                to: request.assigned_to.email,
                subject: rendered.subject,
                html: rendered.html,
                text: rendered.plainText,
              });
            }
          }
        } else if (!request.first_response_at && hoursElapsed >= responseTime * 0.8) {
          // Warning - 80% of SLA time elapsed
          const timeRemaining = responseTime - hoursElapsed;
          warnings.push({
            request,
            type: "response",
            timeRemaining,
          });

          // Send warning if not already warned
          if (!request.sla_warning_sent_at) {
            await supabase.from("requests").update({ sla_warning_sent_at: now.toISOString() }).eq("id", request.id);

            if (request.assigned_to?.email) {
              const templateData: SLABreachData = {
                request: {
                  title: request.title,
                  request_number: request.request_number,
                  priority: request.priority,
                },
                sla: {
                  response_time_hours: responseTime,
                  resolution_time_hours: resolutionTime,
                },
                time_remaining_hours: timeRemaining,
                request_url: `${process.env.NEXT_PUBLIC_APP_URL}/requests/${request.id}`,
              };

              const rendered = await renderEmailTemplate("sla_warning", templateData);
              if (rendered) {
                await sendEmail({
                  to: request.assigned_to.email,
                  subject: rendered.subject,
                  html: rendered.html,
                  text: rendered.plainText,
                });
              }
            }
          }
        }

        // Check resolution SLA (only for requests with first response)
        if (request.first_response_at && request.status === "in_progress") {
          if (hoursElapsed >= resolutionTime && !request.resolved_at) {
            // Resolution SLA breach
            breaches.push({
              request,
              type: "resolution",
              hoursOverdue: hoursElapsed - resolutionTime,
            });

            await supabase
              .from("requests")
              .update({
                sla_breached: true,
                sla_breach_type: "resolution",
                sla_breach_at: now.toISOString(),
              })
              .eq("id", request.id);
          } else if (hoursElapsed >= resolutionTime * 0.8 && !request.resolved_at) {
            // Resolution warning
            const timeRemaining = resolutionTime - hoursElapsed;
            warnings.push({
              request,
              type: "resolution",
              timeRemaining,
            });
          }
        }
      });
    }

    return {
      checked: activeRequests.length,
      warnings: warnings.length,
      breaches: breaches.length,
    };
  },
);
