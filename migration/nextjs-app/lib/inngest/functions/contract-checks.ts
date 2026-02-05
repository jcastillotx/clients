import { inngest } from "../client";
import { createClient } from "@/lib/supabase/server";
import { sendEmail } from "@/lib/email/client";
import { renderEmailTemplate, type ContractExpirationData } from "@/lib/email/templates";

// Check contract expirations daily
export const checkContractExpirations = inngest.createFunction(
  {
    id: "check-contract-expirations",
    name: "Check Contract Expirations",
  },
  { cron: "0 10 * * *" }, // Daily at 10am UTC
  async ({ step }) => {
    const supabase = createClient();

    // Step 1: Find contracts expiring in 30 days
    const expiringSoon = await step.run("find-expiring-soon", async () => {
      const targetDate = new Date();
      targetDate.setDate(targetDate.getDate() + 30);

      const { data } = await supabase
        .from("contracts")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
        `,
        )
        .eq("status", "active")
        .lte("end_date", targetDate.toISOString())
        .gte("end_date", new Date().toISOString())
        .is("notified_expiring_30_at", null);

      return data || [];
    });

    // Step 2: Send 30-day expiration notices
    for (const contract of expiringSoon) {
      await step.run(`notify-expiring-${contract.id}`, async () => {
        if (!contract.client?.email) return;

        const daysUntilExpiration = Math.ceil(
          (new Date(contract.end_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24),
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

        const rendered = await renderEmailTemplate("contract_expiring", templateData);
        if (!rendered) return;

        await sendEmail({
          to: contract.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        await supabase
          .from("contracts")
          .update({ notified_expiring_30_at: new Date().toISOString() })
          .eq("id", contract.id);
      });
    }

    // Step 3: Find contracts expiring in 7 days
    const expiringVeryLate = await step.run("find-expiring-very-soon", async () => {
      const targetDate = new Date();
      targetDate.setDate(targetDate.getDate() + 7);

      const { data } = await supabase
        .from("contracts")
        .select(
          `
          *,
          client:clients(id, company_name, email, contact_name)
        `,
        )
        .eq("status", "active")
        .lte("end_date", targetDate.toISOString())
        .gte("end_date", new Date().toISOString())
        .is("notified_expiring_7_at", null);

      return data || [];
    });

    // Step 4: Send 7-day expiration notices
    for (const contract of expiringVeryLate) {
      await step.run(`notify-expiring-urgent-${contract.id}`, async () => {
        if (!contract.client?.email) return;

        const daysUntilExpiration = Math.ceil(
          (new Date(contract.end_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24),
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

        const rendered = await renderEmailTemplate("contract_expiring_urgent", templateData);
        if (!rendered) return;

        await sendEmail({
          to: contract.client.email,
          subject: rendered.subject,
          html: rendered.html,
          text: rendered.plainText,
        });

        await supabase
          .from("contracts")
          .update({ notified_expiring_7_at: new Date().toISOString() })
          .eq("id", contract.id);
      });
    }

    // Step 5: Mark expired contracts
    const expiredContracts = await step.run("mark-expired-contracts", async () => {
      const { data } = await supabase
        .from("contracts")
        .update({ status: "expired" })
        .eq("status", "active")
        .lt("end_date", new Date().toISOString())
        .eq("auto_renew", false)
        .select();

      return data || [];
    });

    // Step 6: Auto-renew eligible contracts
    const renewedContracts = await step.run("auto-renew-contracts", async () => {
      const { data: autoRenewContracts } = await supabase
        .from("contracts")
        .select("*")
        .eq("status", "active")
        .lt("end_date", new Date().toISOString())
        .eq("auto_renew", true);

      if (!autoRenewContracts) return [];

      const renewed = [];
      for (const contract of autoRenewContracts) {
        // Calculate new end date based on original duration
        const startDate = new Date(contract.start_date);
        const endDate = new Date(contract.end_date);
        const durationDays = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));

        const newStartDate = new Date(contract.end_date);
        const newEndDate = new Date(newStartDate);
        newEndDate.setDate(newEndDate.getDate() + durationDays);

        // Update contract
        const { data: updated } = await supabase
          .from("contracts")
          .update({
            start_date: newStartDate.toISOString(),
            end_date: newEndDate.toISOString(),
            renewed_at: new Date().toISOString(),
            renewed_count: (contract.renewed_count || 0) + 1,
          })
          .eq("id", contract.id)
          .select()
          .single();

        if (updated) renewed.push(updated);
      }

      return renewed;
    });

    return {
      expiringSoon: expiringSoon.length,
      expiringVeryLate: expiringVeryLate.length,
      expired: expiredContracts.length,
      renewed: renewedContracts.length,
    };
  },
);
