import { PublicProjectRequestForm } from "@/components/projects/public-project-request-form";

export const metadata = {
  title: "Request a Project | KRE8IV",
  description: "Submit a project request for your organization",
};

export const dynamic = "force-dynamic";

export default async function PublicProjectRequestPage({
  searchParams,
}: {
  searchParams: Promise<{ package?: string }>;
}) {
  const packageId = (await searchParams).package;
  let selectedPackage: { id: string; name: string } | undefined;
  if (packageId && /^[a-zA-Z0-9-]{1,100}$/.test(packageId)) {
    try {
      const { getPublicServiceCatalog } =
        await import("@/lib/catalog/public-catalog");
      const catalog = await getPublicServiceCatalog();
      const item = catalog.packages.find((item) => item.id === packageId);
      if (item) selectedPackage = { id: item.id, name: item.name };
    } catch {
      // The general intake remains available when the optional catalog is unavailable.
    }
  }
  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-secondary/30 to-background px-6 py-12">
      <div className="mx-auto max-w-5xl">
        <PublicProjectRequestForm selectedPackage={selectedPackage} />
      </div>
    </div>
  );
}
