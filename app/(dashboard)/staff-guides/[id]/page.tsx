import { StaffGuideDetail } from "@/components/staff-guides/guide-detail";

export const metadata = {
  title: "Staff Guide | Dashboard",
  description: "Internal staff guide",
};

type PageProps = {
  params: Promise<{ id: string }>;
};

export default async function StaffGuidePage({ params }: PageProps) {
  const { id } = await params;

  return (
    <div className="container mx-auto max-w-4xl py-6">
      <StaffGuideDetail guideId={id} />
    </div>
  );
}
