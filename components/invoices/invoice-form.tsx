"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useForm, useFieldArray } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Separator } from "@/components/ui/separator";
import { createClient } from "@/lib/supabase/client";
import { calculateInvoiceTotals } from "@/lib/invoices/calculate-totals";
import { calculateNextRecurringDate, type RecurringInterval } from "@/lib/invoices/recurring";
import { formatTaxLocationLabel, getTaxRateForClient } from "@/lib/invoices/tax-rates";
import { createInvoiceSchema, type CreateInvoiceInput } from "@/lib/validations/invoice";
import { ChevronDown, Loader2, Plus, Trash2 } from "lucide-react";
import { cn } from "@/lib/utils";

export interface InvoiceClientOption {
  id: string;
  company_name: string;
  city?: string | null;
  state?: string | null;
  country?: string | null;
}

interface InvoiceFormProps {
  clients: InvoiceClientOption[];
  preselectedClientId?: string;
}

function getInvoiceFormErrorMessage(error: unknown): string {
  if (error instanceof Error) {
    return error.message;
  }

  if (error && typeof error === "object") {
    const record = error as Record<string, unknown>;

    if (typeof record.message === "string" && record.message.trim().length > 0) {
      return record.message;
    }

    if (typeof record.details === "string" && record.details.trim().length > 0) {
      return record.details;
    }
  }

  return "Failed to create invoice";
}

export function InvoiceForm({ clients, preselectedClientId }: InvoiceFormProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [taxRateManuallySet, setTaxRateManuallySet] = useState(false);
  const [expandedItemIndexes, setExpandedItemIndexes] = useState<Set<number>>(new Set());

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
    control,
  } = useForm<CreateInvoiceInput>({
    resolver: zodResolver(createInvoiceSchema),
    defaultValues: {
      clientId: preselectedClientId || "",
      invoiceNumber: `INV-${Date.now()}`,
      billingType: "single",
      recurringInterval: "monthly",
      taxRate: 0,
      discountType: "none",
      discountValue: 0,
      items: [{ description: "", details: "", quantity: 1, unitPrice: 0 }],
    },
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: "items",
  });

  const clientId = watch("clientId");
  const items = watch("items");
  const taxRate = watch("taxRate");
  const discountType = watch("discountType");
  const discountValue = watch("discountValue");
  const billingType = watch("billingType");
  const recurringInterval = watch("recurringInterval");

  const selectedClient = useMemo(
    () => clients.find((client) => client.id === clientId),
    [clients, clientId],
  );

  const suggestedTaxRate = useMemo(() => {
    if (!selectedClient) return 0;
    return getTaxRateForClient(selectedClient);
  }, [selectedClient]);

  useEffect(() => {
    if (!clientId || taxRateManuallySet) return;
    setValue("taxRate", suggestedTaxRate);
  }, [clientId, suggestedTaxRate, taxRateManuallySet, setValue]);

  const totals = calculateInvoiceTotals({
    items: items.map((item) => ({
      quantity: Number(item.quantity) || 0,
      unitPrice: Number(item.unitPrice) || 0,
    })),
    taxRate: Number(taxRate) || 0,
    discountType: discountType ?? "none",
    discountValue: Number(discountValue) || 0,
  });

  const toggleItemDetails = (index: number) => {
    setExpandedItemIndexes((current) => {
      const next = new Set(current);
      if (next.has(index)) {
        next.delete(index);
      } else {
        next.add(index);
      }
      return next;
    });
  };

  const onSubmit = async (data: CreateInvoiceInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const supabase = createClient();
      const {
        data: { user },
      } = await supabase.auth.getUser();

      if (!user) throw new Error("Not authenticated");

      const computedTotals = calculateInvoiceTotals({
        items: data.items.map((item) => ({
          quantity: item.quantity,
          unitPrice: item.unitPrice,
        })),
        taxRate: data.taxRate,
        discountType: data.discountType,
        discountValue: data.discountValue,
      });
      const isRecurring = data.billingType === "recurring";
      const selectedRecurringInterval = data.recurringInterval ?? "monthly";

      const { data: invoice, error: invoiceError } = await supabase
        .from("invoices")
        .insert({
          client_id: data.clientId,
          invoice_number: data.invoiceNumber,
          amount: computedTotals.total,
          subtotal: computedTotals.subtotal,
          tax_rate: data.taxRate,
          tax_amount: computedTotals.taxAmount,
          discount_type: data.discountType,
          discount_value: data.discountValue,
          discount_amount: computedTotals.discountAmount,
          status: "draft",
          due_date: data.dueDate || null,
          notes: data.notes || null,
          is_recurring: isRecurring,
          recurring_interval: isRecurring ? selectedRecurringInterval : null,
          next_recurring_date: isRecurring
            ? calculateNextRecurringDate(new Date(), selectedRecurringInterval).toISOString()
            : null,
          created_by: user.id,
        })
        .select()
        .single();

      if (invoiceError) throw new Error(invoiceError.message);

      const itemsToInsert = data.items.map((item) => ({
        invoice_id: invoice.id,
        description: item.description,
        details: item.details?.trim() || null,
        quantity: item.quantity,
        unit_price: item.unitPrice,
        amount: item.quantity * item.unitPrice,
      }));

      const { error: itemsError } = await supabase.from("invoice_items").insert(itemsToInsert);

      if (itemsError) {
        const { error: cleanupError } = await supabase.from("invoices").delete().eq("id", invoice.id);

        if (cleanupError) {
          console.error("Failed to clean up invoice after item insert error:", cleanupError);
        }

        throw new Error(itemsError.message);
      }

      router.push(`/invoices/${invoice.id}`);
    } catch (err) {
      console.error("Failed to create invoice:", err);
      setError(getInvoiceFormErrorMessage(err));
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <Card>
        <CardHeader>
          <CardTitle>Invoice Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="clientId">
                Client <span className="text-destructive">*</span>
              </Label>
              <Select
                value={clientId}
                onValueChange={(value) => {
                  setTaxRateManuallySet(false);
                  setValue("clientId", value);
                }}
              >
                <SelectTrigger id="clientId">
                  <SelectValue placeholder="Select a client" />
                </SelectTrigger>
                <SelectContent>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.clientId && <p className="text-sm text-destructive">{errors.clientId.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="invoiceNumber">
                Invoice Number <span className="text-destructive">*</span>
              </Label>
              <Input id="invoiceNumber" placeholder="INV-001" {...register("invoiceNumber")} />
              {errors.invoiceNumber && <p className="text-sm text-destructive">{errors.invoiceNumber.message}</p>}
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="dueDate">Due Date</Label>
              <Input id="dueDate" type="date" {...register("dueDate")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="billingType">Billing Type</Label>
              <Select
                value={billingType}
                onValueChange={(value: CreateInvoiceInput["billingType"]) => {
                  setValue("billingType", value, { shouldValidate: true });
                  if (value === "single") {
                    setValue("recurringInterval", undefined);
                  } else if (!recurringInterval) {
                    setValue("recurringInterval", "monthly", { shouldValidate: true });
                  }
                }}
              >
                <SelectTrigger id="billingType">
                  <SelectValue placeholder="Select billing type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="single">Single invoice</SelectItem>
                  <SelectItem value="recurring">Recurring invoice</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          {billingType === "recurring" && (
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="recurringInterval">Recurring Cycle</Label>
                <Select
                  value={recurringInterval ?? "monthly"}
                  onValueChange={(value: RecurringInterval) => {
                    setValue("recurringInterval", value, { shouldValidate: true });
                  }}
                >
                  <SelectTrigger id="recurringInterval">
                    <SelectValue placeholder="Select cycle" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="weekly">Weekly</SelectItem>
                    <SelectItem value="monthly">Monthly</SelectItem>
                    <SelectItem value="quarterly">Quarterly</SelectItem>
                    <SelectItem value="yearly">Yearly</SelectItem>
                  </SelectContent>
                </Select>
                {errors.recurringInterval && (
                  <p className="text-sm text-destructive">{errors.recurringInterval.message}</p>
                )}
              </div>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea id="notes" placeholder="Additional notes..." rows={3} {...register("notes")} />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Tax & Discount</CardTitle>
          <CardDescription>
            Tax is suggested from the client&apos;s billing location. You can override the rate below.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="taxRate">Tax Rate (%)</Label>
              <Input
                id="taxRate"
                type="number"
                step="0.001"
                min="0"
                max="100"
                {...register("taxRate", {
                  onChange: () => setTaxRateManuallySet(true),
                })}
              />
              {errors.taxRate && <p className="text-sm text-destructive">{errors.taxRate.message}</p>}
              {selectedClient && (
                <p className="text-xs text-muted-foreground">
                  {formatTaxLocationLabel(selectedClient)} — suggested {suggestedTaxRate.toFixed(2)}%
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="discountType">Discount Type</Label>
              <Select
                value={discountType}
                onValueChange={(value: CreateInvoiceInput["discountType"]) => setValue("discountType", value)}
              >
                <SelectTrigger id="discountType">
                  <SelectValue placeholder="No discount" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No discount</SelectItem>
                  <SelectItem value="percentage">Percentage (%)</SelectItem>
                  <SelectItem value="fixed">Fixed amount ($)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="discountValue">
                {discountType === "percentage" ? "Discount (%)" : "Discount ($)"}
              </Label>
              <Input
                id="discountValue"
                type="number"
                step="0.01"
                min="0"
                disabled={discountType === "none"}
                {...register("discountValue")}
              />
              {errors.discountValue && <p className="text-sm text-destructive">{errors.discountValue.message}</p>}
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0">
          <div>
            <CardTitle>Invoice Items</CardTitle>
            <CardDescription>Add line items and optional details for each service or product.</CardDescription>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => append({ description: "", details: "", quantity: 1, unitPrice: 0 })}
          >
            <Plus className="mr-2 h-4 w-4" />
            Add Item
          </Button>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Description</TableHead>
                  <TableHead className="w-32">Quantity</TableHead>
                  <TableHead className="w-32">Unit Price</TableHead>
                  <TableHead className="w-32">Amount</TableHead>
                  <TableHead className="w-12"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {fields.map((field, index) => {
                  const lineTotal =
                    (Number(items[index]?.quantity) || 0) * (Number(items[index]?.unitPrice) || 0);
                  const isExpanded = expandedItemIndexes.has(index);

                  return (
                    <TableRow key={field.id} className="align-top">
                      <TableCell>
                        <div className="space-y-2">
                          <div className="flex gap-2">
                            <Input
                              placeholder="Item description"
                              className="flex-1"
                              {...register(`items.${index}.description`)}
                            />
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              className="shrink-0 px-2"
                              onClick={() => toggleItemDetails(index)}
                              aria-expanded={isExpanded}
                              aria-label="Toggle additional details"
                            >
                              <ChevronDown className={cn("h-4 w-4 transition-transform", isExpanded && "rotate-180")} />
                            </Button>
                          </div>
                          {isExpanded && (
                            <Textarea
                              placeholder="Additional details (scope, deliverables, notes...)"
                              rows={3}
                              {...register(`items.${index}.details`)}
                            />
                          )}
                          {errors.items?.[index]?.description && (
                            <p className="text-xs text-destructive">{errors.items[index]?.description?.message}</p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Input type="number" step="0.01" placeholder="1" {...register(`items.${index}.quantity`)} />
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          {...register(`items.${index}.unitPrice`)}
                        />
                      </TableCell>
                      <TableCell className="font-semibold">${lineTotal.toFixed(2)}</TableCell>
                      <TableCell>
                        {fields.length > 1 && (
                          <Button type="button" variant="ghost" size="sm" onClick={() => remove(index)}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>

            {errors.items?.root && <p className="text-sm text-destructive">{errors.items.root.message}</p>}

            <Separator />

            <div className="flex justify-end">
              <div className="w-full max-w-xs space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span>${totals.subtotal.toFixed(2)}</span>
                </div>
                {totals.discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-green-700">
                    <span>Discount</span>
                    <span>-${totals.discountAmount.toFixed(2)}</span>
                  </div>
                )}
                {totals.taxAmount > 0 && (
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Tax ({Number(taxRate).toFixed(2)}%)</span>
                    <span>${totals.taxAmount.toFixed(2)}</span>
                  </div>
                )}
                <Separator />
                <div className="flex justify-between font-bold text-lg">
                  <span>Total</span>
                  <span>${totals.total.toFixed(2)}</span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="flex gap-4">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Create Invoice
        </Button>
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
