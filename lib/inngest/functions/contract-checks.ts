import { inngest } from "../client";
import {
  createJobClient,
  scheduledJobsEnabled,
  assertJobResult,
} from "../job-runtime";
import { sendEmail } from "@/lib/email/client";
import {
  renderEmailTemplateWithClient,
  type ContractExpirationData,
} from "@/lib/email/templates";

// Check contract expirations daily
export const checkContractExpirations = inngest.createFunction(
  {
    id: "check-contract-expirations",
    name: "Check Contract Expirations",
    concurrency: 1,
  },
  { cron: "0 10 * * *" }, // Daily at 10am UTC
  async ({ step }) => {
    if (!scheduledJobsEnabled())
      return { skipped: true, reason: "schedules_disabled" };
    const supabase = createJobClient();

    // Step 1: Find contracts expiring in 30 days
    const expiringSoon = await step.run("find-expiring-soon", async () => {
      const targetDate = new Date();
      targetDate.setDate(targetDate.getDate() + 30);

      const { data, error } = await supabase
        .from("contracts")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
        `,
        )
        .eq("status", "active")
        .is("deleted_at", null)
        .lte("end_date", targetDate.toISOString())
        .gt("end_date", new Date(Date.now() + 7 * 86400000).toISOString())
        .is("notified_expiring_30_at", null);

      assertJobResult(error, "Contract schedule query");
      return data || [];
    });

    // Step 2: Send 30-day expiration notices
    for (const contract of expiringSoon) {
      await step.run(`notify-expiring-${contract.id}`, async () => {
        if (!contract.client?.email)
          throw new Error("Contract reminder recipient unavailable");

        const daysUntilExpiration = Math.ceil(
          (new Date(contract.end_date).getTime() - new Date().getTime()) /
            (1000 * 60 * 60 * 24),
        );

        const templateData: ContractExpirationData = {
          contract: {
            title: contract.title,
            contract_number: contract.contract_number,
            end_date: contract.end_date,
          },
          client: {
            company_name: contract.client.company_name,
          },
          days_until_expiration: daysUntilExpiration,
          auto_renew: contract.auto_renew,
          contract_url: `${process.env.NEXT_PUBLIC_APP_URL}/contracts/${contract.id}`,
        };

        const rendered = await renderEmailTemplateWithClient(
          supabase,
          "contract_expiring",
          {
            ...templateData,
            contract_title: contract.title,
            contract_number: contract.contract_number,
            end_date: contract.end_date,
            company_name: contract.client.company_name,
          },
        );
        if (!rendered)
          throw new Error("Contract expiration email template unavailable");

        const sent = await sendEmail({
          to: contract.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        if (!sent.success)
          throw new Error("Contract expiration email delivery failed");
        const { error } = await supabase
          .from("contracts")
          .update({ notified_expiring_30_at: new Date().toISOString() })
          .eq("id", contract.id);
        assertJobResult(error, "Save contract notification");
      });
    }

    // Step 3: Find contracts expiring in 7 days
    const expiringVeryLate = await step.run(
      "find-expiring-very-soon",
      async () => {
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 7);

        const { data, error } = await supabase
          .from("contracts")
          .select(
            `
          *,
          client:clients(id, company_name, email, contact_name)
        `,
          )
          .eq("status", "active")
          .is("deleted_at", null)
          .lte("end_date", targetDate.toISOString())
          .gte("end_date", new Date().toISOString())
          .is("notified_expiring_7_at", null);

        assertJobResult(error, "Contract schedule query");
        return data || [];
      },
    );

    // Step 4: Send 7-day expiration notices
    for (const contract of expiringVeryLate) {
      await step.run(`notify-expiring-urgent-${contract.id}`, async () => {
        if (!contract.client?.email)
          throw new Error("Contract reminder recipient unavailable");

        const daysUntilExpiration = Math.ceil(
          (new Date(contract.end_date).getTime() - new Date().getTime()) /
            (1000 * 60 * 60 * 24),
        );

        const templateData: ContractExpirationData = {
          contract: {
            title: contract.title,
            contract_number: contract.contract_number,
            end_date: contract.end_date,
          },
          client: {
            company_name: contract.client.company_name,
          },
          days_until_expiration: daysUntilExpiration,
          auto_renew: contract.auto_renew,
          contract_url: `${process.env.NEXT_PUBLIC_APP_URL}/contracts/${contract.id}`,
        };

        const rendered = await renderEmailTemplateWithClient(
          supabase,
          "contract_expiring_urgent",
          {
            ...templateData,
            contract_title: contract.title,
            contract_number: contract.contract_number,
            end_date: contract.end_date,
            company_name: contract.client.company_name,
          },
        );
        if (!rendered)
          throw new Error("Contract expiration email template unavailable");

        const sent = await sendEmail({
          to: contract.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        if (!sent.success)
          throw new Error("Contract expiration email delivery failed");
        const { error } = await supabase
          .from("contracts")
          .update({ notified_expiring_7_at: new Date().toISOString() })
          .eq("id", contract.id);
        assertJobResult(error, "Save contract notification");
      });
    }

    // Step 5: Mark expired contracts
    const expiredContracts = await step.run(
      "mark-expired-contracts",
      async () => {
        const { data, error } = await supabase
          .from("contracts")
          .update({ status: "expired" })
          .eq("status", "active")
          .is("deleted_at", null)
          .lt("end_date", new Date().toISOString())
          .eq("auto_renew", false)
          .select();

        assertJobResult(error, "Contract schedule query");
        return data || [];
      },
    );

    // Step 6: Auto-renew eligible contracts
    const renewedContracts = await step.run(
      "auto-renew-contracts",
      async () => {
        const { data: autoRenewContracts, error: renewQueryError } =
          await supabase
            .from("contracts")
            .select("*")
            .eq("status", "active")
            .is("deleted_at", null)
            .lt("end_date", new Date().toISOString())
            .eq("auto_renew", true);

        assertJobResult(renewQueryError, "Read auto-renew contracts");
        if (!autoRenewContracts) return [];

        const renewed = [];
        for (const contract of autoRenewContracts) {
          // Calculate new end date based on original duration
          const startDate = new Date(contract.start_date);
          const endDate = new Date(contract.end_date);
          const durationDays = Math.ceil(
            (endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24),
          );

          if (!Number.isFinite(durationDays) || durationDays <= 0)
            throw new Error("Invalid contract renewal duration");
          const newStartDate = new Date(contract.end_date);
          const newEndDate = new Date(newStartDate);
          newEndDate.setDate(newEndDate.getDate() + durationDays);

          // Update contract
          const { data: updated, error: renewalError } = await supabase
            .from("contracts")
            .update({
              start_date: newStartDate.toISOString(),
              end_date: newEndDate.toISOString(),
              renewed_at: new Date().toISOString(),
              renewed_count: (contract.renewed_count || 0) + 1,
              notified_expiring_30_at: null,
              notified_expiring_7_at: null,
            })
            .eq("id", contract.id)
            .select()
            .single();

          assertJobResult(renewalError, "Renew contract");
          if (updated) renewed.push(updated);
        }

        return renewed;
      },
    );

    return {
      expiringSoon: expiringSoon.length,
      expiringVeryLate: expiringVeryLate.length,
      expired: expiredContracts.length,
      renewed: renewedContracts.length,
    };
  },
);
