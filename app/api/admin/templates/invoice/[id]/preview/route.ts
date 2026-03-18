import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { compileTemplate } from "@/lib/templates/template-engine";
import { requirePermission } from "@/lib/auth/route-guards";

type PreviewContext = {
  invoice_number: string;
  invoice_date: string;
  due_date: string;
  company_name: string;
  client_name: string;
  subtotal: string;
  tax_amount: string;
  total: string;
  items_html: string;
};

function buildPreviewContext(): PreviewContext {
  return {
    invoice_number: "INV-1001",
    invoice_date: new Date().toLocaleDateString(),
    due_date: new Date(
      Date.now() + 1000 * 60 * 60 * 24 * 14,
    ).toLocaleDateString(),
    company_name: "KRE8IV Designs",
    client_name: "Sample Client, Inc.",
    subtotal: "1,200.00",
    tax_amount: "96.00",
    total: "1,296.00",
    items_html: `
      <tr>
        <td>Website management retainer</td>
        <td style="text-align:right">1</td>
        <td style="text-align:right">$1,200.00</td>
      </tr>
    `,
  };
}

function injectCss(renderedHtml: string, cssContent: string | null): string {
  if (!cssContent?.trim()) {
    return renderedHtml;
  }

  const styleTag = `<style>${cssContent}</style>`;
  if (renderedHtml.includes("</head>")) {
    return renderedHtml.replace("</head>", `${styleTag}</head>`);
  }

  return `<!DOCTYPE html><html><head><meta charset="utf-8" />${styleTag}</head><body>${renderedHtml}</body></html>`;
}

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;

  try {
    const guard = await requirePermission("settings.manage");
    if ("error" in guard) {
      return guard.error;
    }

    const supabase = await createClient();
    const { data: template, error } = await supabase
      .from("invoice_templates")
      .select("id, name, html_content, css_content, is_active, deleted_at")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error || !template) {
      return NextResponse.json(
        { error: "Template not found" },
        { status: 404 },
      );
    }

    const renderedBody = compileTemplate(
      template.html_content,
      buildPreviewContext(),
    );
    const html = injectCss(renderedBody, template.css_content);

    return new NextResponse(html, {
      status: 200,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
        "Cache-Control": "no-store",
      },
    });
  } catch (error) {
    console.error("Error previewing invoice template:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to preview template",
      },
      { status: 500 },
    );
  }
}
