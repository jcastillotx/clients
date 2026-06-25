import { Document, Page, Text, View, StyleSheet } from "@react-pdf/renderer";
import { format } from "date-fns";

const styles = StyleSheet.create({
  page: {
    padding: 40,
    fontSize: 10,
    fontFamily: "Helvetica",
  },
  header: {
    marginBottom: 30,
  },
  title: {
    fontSize: 24,
    fontWeight: "bold",
    marginBottom: 10,
  },
  invoiceNumber: {
    fontSize: 12,
    color: "#666",
  },
  section: {
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: "bold",
    marginBottom: 8,
    color: "#333",
  },
  row: {
    flexDirection: "row",
    marginBottom: 4,
  },
  label: {
    width: 100,
    fontWeight: "bold",
  },
  value: {
    flex: 1,
  },
  table: {
    marginTop: 20,
    marginBottom: 20,
  },
  tableHeader: {
    flexDirection: "row",
    borderBottomWidth: 2,
    borderBottomColor: "#333",
    paddingBottom: 8,
    marginBottom: 8,
    fontWeight: "bold",
  },
  tableRow: {
    flexDirection: "row",
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: "#eee",
  },
  colDescription: {
    flex: 3,
  },
  colQuantity: {
    width: 60,
    textAlign: "right",
  },
  colPrice: {
    width: 80,
    textAlign: "right",
  },
  colAmount: {
    width: 80,
    textAlign: "right",
  },
  totals: {
    marginTop: 20,
    alignItems: "flex-end",
  },
  totalRow: {
    flexDirection: "row",
    marginBottom: 4,
    width: 200,
  },
  totalLabel: {
    flex: 1,
    textAlign: "right",
    marginRight: 20,
  },
  totalValue: {
    width: 80,
    textAlign: "right",
  },
  grandTotalRow: {
    flexDirection: "row",
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 2,
    borderTopColor: "#333",
    width: 200,
    fontWeight: "bold",
    fontSize: 12,
  },
  footer: {
    position: "absolute",
    bottom: 40,
    left: 40,
    right: 40,
    textAlign: "center",
    color: "#666",
    fontSize: 9,
  },
  status: {
    marginTop: 20,
    padding: 10,
    backgroundColor: "#f5f5f5",
    borderRadius: 4,
  },
  statusText: {
    fontSize: 11,
    fontWeight: "bold",
  },
  notes: {
    marginTop: 20,
    padding: 10,
    backgroundColor: "#f9f9f9",
    borderRadius: 4,
  },
  notesTitle: {
    fontSize: 11,
    fontWeight: "bold",
    marginBottom: 6,
  },
  notesText: {
    fontSize: 9,
    lineHeight: 1.5,
  },
  itemDetails: {
    fontSize: 8,
    color: "#666",
    marginTop: 2,
    lineHeight: 1.4,
  },
});

interface InvoicePDFProps {
  invoice: {
    invoice_number: string;
    status: string;
    created_at: string;
    due_date?: string;
    paid_at?: string;
    notes?: string;
    client: {
      company_name: string;
      domain?: string;
      primary_contact?: {
        name: string;
        email: string;
      };
    };
    invoice_items: Array<{
      description: string;
      details?: string | null;
      quantity: number;
      unit_price: number;
      amount: number;
    }>;
    amount: number;
    subtotal?: number | null;
    tax_rate?: number | null;
    tax_amount?: number | null;
    discount_amount?: number | null;
  };
}

export function InvoicePDF({ invoice }: InvoicePDFProps) {
  const subtotal =
    invoice.subtotal != null
      ? Number(invoice.subtotal)
      : invoice.invoice_items.reduce((sum, item) => sum + item.amount, 0);
  const discountAmount = Number(invoice.discount_amount ?? 0);
  const taxAmount = Number(invoice.tax_amount ?? 0);
  const total = Number(invoice.amount);

  return (
    <Document>
      <Page size="A4" style={styles.page}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.title}>INVOICE</Text>
          <Text style={styles.invoiceNumber}>{invoice.invoice_number}</Text>
        </View>

        {/* Company Info - You can replace this with actual company details */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>From</Text>
          <Text>Your Company Name</Text>
          <Text>123 Business Street</Text>
          <Text>City, State 12345</Text>
          <Text>contact@yourcompany.com</Text>
        </View>

        {/* Client Info */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Bill To</Text>
          <Text>{invoice.client.company_name}</Text>
          {invoice.client.domain && <Text>{invoice.client.domain}</Text>}
          {invoice.client.primary_contact && (
            <>
              <Text>{invoice.client.primary_contact.name}</Text>
              <Text>{invoice.client.primary_contact.email}</Text>
            </>
          )}
        </View>

        {/* Invoice Details */}
        <View style={styles.section}>
          <View style={styles.row}>
            <Text style={styles.label}>Invoice Date:</Text>
            <Text style={styles.value}>{format(new Date(invoice.created_at), "MMMM d, yyyy")}</Text>
          </View>
          {invoice.due_date && (
            <View style={styles.row}>
              <Text style={styles.label}>Due Date:</Text>
              <Text style={styles.value}>{format(new Date(invoice.due_date), "MMMM d, yyyy")}</Text>
            </View>
          )}
          {invoice.paid_at && (
            <View style={styles.row}>
              <Text style={styles.label}>Paid Date:</Text>
              <Text style={styles.value}>{format(new Date(invoice.paid_at), "MMMM d, yyyy")}</Text>
            </View>
          )}
        </View>

        {/* Status Badge */}
        {invoice.status && (
          <View style={styles.status}>
            <Text style={styles.statusText}>Status: {invoice.status.toUpperCase()}</Text>
          </View>
        )}

        {/* Invoice Items Table */}
        <View style={styles.table}>
          <View style={styles.tableHeader}>
            <Text style={styles.colDescription}>Description</Text>
            <Text style={styles.colQuantity}>Qty</Text>
            <Text style={styles.colPrice}>Unit Price</Text>
            <Text style={styles.colAmount}>Amount</Text>
          </View>
          {invoice.invoice_items.map((item, index) => (
            <View key={index} style={styles.tableRow}>
              <View style={styles.colDescription}>
                <Text>{item.description}</Text>
                {item.details ? <Text style={styles.itemDetails}>{item.details}</Text> : null}
              </View>
              <Text style={styles.colQuantity}>{item.quantity}</Text>
              <Text style={styles.colPrice}>${item.unit_price.toFixed(2)}</Text>
              <Text style={styles.colAmount}>${item.amount.toFixed(2)}</Text>
            </View>
          ))}
        </View>

        {/* Totals */}
        <View style={styles.totals}>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Subtotal:</Text>
            <Text style={styles.totalValue}>${subtotal.toFixed(2)}</Text>
          </View>
          {discountAmount > 0 && (
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Discount:</Text>
              <Text style={styles.totalValue}>-${discountAmount.toFixed(2)}</Text>
            </View>
          )}
          {taxAmount > 0 && (
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>
                Tax{invoice.tax_rate != null ? ` (${Number(invoice.tax_rate).toFixed(2)}%)` : ""}:
              </Text>
              <Text style={styles.totalValue}>${taxAmount.toFixed(2)}</Text>
            </View>
          )}
          <View style={styles.grandTotalRow}>
            <Text style={styles.totalLabel}>Total:</Text>
            <Text style={styles.totalValue}>${total.toFixed(2)}</Text>
          </View>
        </View>

        {/* Notes */}
        {invoice.notes && (
          <View style={styles.notes}>
            <Text style={styles.notesTitle}>Notes</Text>
            <Text style={styles.notesText}>{invoice.notes}</Text>
          </View>
        )}

        {/* Footer */}
        <View style={styles.footer}>
          <Text>Thank you for your business!</Text>
          <Text>Please make payment by the due date.</Text>
        </View>
      </Page>
    </Document>
  );
}
