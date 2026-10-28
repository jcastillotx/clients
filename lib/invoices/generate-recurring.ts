import { createHash } from "node:crypto";
import { and, eq, isNull } from "drizzle-orm";
import { db } from "@/lib/db";
import { invoices, invoiceItems } from "@/lib/db/schema/invoices";
import {
  calculateNextRecurringDate,
  recurringIntervals,
  type RecurringInterval,
} from "@/lib/invoices/recurring";

export function recurringInvoiceId(templateId: string, period: string): string {
  const hex = createHash("sha256")
    .update(`${templateId}:${new Date(period).toISOString()}`)
    .digest("hex");
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-5${hex.slice(13, 16)}-a${hex.slice(17, 20)}-${hex.slice(20, 32)}`;
}

/** A row lock and atomic commit prevent partial or duplicate invoices on retries. */
export async function generateRecurringInvoice(
  templateId: string,
  period: string,
) {
  if (new Date(period).getTime() > Date.now()) return null;
  const id = recurringInvoiceId(templateId, period);
  return db.transaction(async (tx) => {
    const [template] = await tx
      .select()
      .from(invoices)
      .where(and(eq(invoices.id, templateId), isNull(invoices.deletedAt)))
      .for("update");
    if (!template || !template.isRecurring || template.status === "cancelled")
      return null;
    const [existing] = await tx
      .select()
      .from(invoices)
      .where(eq(invoices.id, id));
    if (existing) return existing;
    if (
      template.nextRecurringDate?.toISOString() !==
      new Date(period).toISOString()
    )
      return null;
    if (
      !template.recurringInterval ||
      !recurringIntervals.includes(template.recurringInterval)
    )
      throw new Error("Invalid recurring invoice interval");
    const now = new Date();
    const nextDueDate = new Date(now.getTime() + 30 * 86400000);
    const nextRecurringDate = calculateNextRecurringDate(
      now,
      template.recurringInterval as RecurringInterval,
    );
    const [invoice] = await tx
      .insert(invoices)
      .values({
        id,
        invoiceNumber: `REC-${id}`,
        clientId: template.clientId,
        dueDate: nextDueDate,
        amount: template.amount,
        subtotal: template.subtotal,
        taxRate: template.taxRate,
        taxAmount: template.taxAmount,
        discountType: template.discountType,
        discountValue: template.discountValue,
        discountAmount: template.discountAmount,
        status: "draft",
        notes: template.notes,
        items: template.items,
        isRecurring: false,
        createdBy: template.createdBy,
      })
      .returning();
    const items = await tx
      .select()
      .from(invoiceItems)
      .where(eq(invoiceItems.invoiceId, templateId));
    if (items.length)
      await tx.insert(invoiceItems).values(
        items.map((item) => ({
          invoiceId: id,
          description: item.description,
          details: item.details,
          quantity: item.quantity,
          unitPrice: item.unitPrice,
          amount: item.amount,
        })),
      );
    await tx
      .update(invoices)
      .set({ nextRecurringDate, updatedAt: now })
      .where(eq(invoices.id, templateId));
    return invoice;
  });
}
