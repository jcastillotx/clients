"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Separator } from "@/components/ui/separator";

interface InvoiceItem {
  id: string;
  description: string;
  details?: string | null;
  quantity: number;
  unit_price: number;
  amount: number;
}

interface InvoiceItemsProps {
  items: InvoiceItem[];
  subtotal?: number | null;
  taxRate?: number | null;
  taxAmount?: number | null;
  discountAmount?: number | null;
  total: number;
}

function formatMoney(value: number): string {
  return value.toLocaleString("en-US", { minimumFractionDigits: 2 });
}

export function InvoiceItems({
  items,
  subtotal,
  taxRate,
  taxAmount,
  discountAmount,
  total,
}: InvoiceItemsProps) {
  const computedSubtotal =
    subtotal ?? items.reduce((sum, item) => sum + Number(item.amount), 0);
  const showDiscount = (discountAmount ?? 0) > 0;
  const showTax = (taxAmount ?? 0) > 0;

  return (
    <Card>
      <CardHeader>
        <CardTitle>Invoice Items</CardTitle>
      </CardHeader>
      <CardContent>
        {items.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-8">No items added</p>
        ) : (
          <div className="space-y-4">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Description</TableHead>
                  <TableHead className="text-right">Quantity</TableHead>
                  <TableHead className="text-right">Unit Price</TableHead>
                  <TableHead className="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell>
                      <div className="font-medium">{item.description}</div>
                      {item.details && (
                        <p className="mt-1 text-sm text-muted-foreground whitespace-pre-wrap">{item.details}</p>
                      )}
                    </TableCell>
                    <TableCell className="text-right">{parseFloat(item.quantity.toString()).toLocaleString()}</TableCell>
                    <TableCell className="text-right">${formatMoney(Number(item.unit_price))}</TableCell>
                    <TableCell className="text-right font-semibold">${formatMoney(Number(item.amount))}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>

            <Separator />

            <div className="flex justify-end">
              <div className="w-full max-w-xs space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span>${formatMoney(Number(computedSubtotal))}</span>
                </div>
                {showDiscount && (
                  <div className="flex justify-between text-sm text-green-700">
                    <span>Discount</span>
                    <span>-${formatMoney(Number(discountAmount))}</span>
                  </div>
                )}
                {showTax && (
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">
                      Tax{taxRate != null ? ` (${Number(taxRate).toFixed(2)}%)` : ""}
                    </span>
                    <span>${formatMoney(Number(taxAmount))}</span>
                  </div>
                )}
                <Separator />
                <div className="flex justify-between font-bold text-lg">
                  <span>Total</span>
                  <span>${formatMoney(Number(total))}</span>
                </div>
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
