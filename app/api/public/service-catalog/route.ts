import { NextResponse } from "next/server";
import { getPublicServiceCatalog } from "@/lib/catalog/public-catalog";
export const dynamic = "force-dynamic";
export async function GET() {
  try {
    const catalog = await getPublicServiceCatalog();
    return NextResponse.json(catalog, {
      headers: {
        "Cache-Control": "public, max-age=0, s-maxage=60",
        ETag: `"${catalog.version}"`,
      },
    });
  } catch {
    console.error("Public service catalog unavailable");
    return NextResponse.json(
      { error: "Service packages are temporarily unavailable." },
      { status: 503, headers: { "Cache-Control": "no-store" } },
    );
  }
}
