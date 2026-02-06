import { Suspense } from "react";
import { StaffGuidesGrid } from "@/components/staff-guides/guides-grid";
import { StaffGuidesStats } from "@/components/staff-guides/guides-stats";
import { StaffGuidesSidebar } from "@/components/staff-guides/guides-sidebar";
import { Button } from "@/components/ui/button";
import { Plus, Lock } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Staff Guides | Dashboard",
  description: "Internal guides, SOPs, and training materials",
};

export default function StaffGuidesPage() {
  return (
    <div className="container mx-auto py-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-3xl font-bold tracking-tight">Staff Guides</h1>
            <Lock className="h-5 w-5 text-muted-foreground" />
          </div>
          <p className="text-muted-foreground mt-1">
            Internal SOPs, training materials, and service tier documentation
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" asChild>
            <Link href="/staff-guides/categories">Manage Categories</Link>
          </Button>
          <Button asChild>
            <Link href="/staff-guides/new">
              <Plus className="mr-2 h-4 w-4" />
              New Guide
            </Link>
          </Button>
        </div>
      </div>

      <Suspense fallback={<div>Loading stats...</div>}>
        <StaffGuidesStats />
      </Suspense>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <div className="md:col-span-1">
          <Suspense fallback={<div>Loading categories...</div>}>
            <StaffGuidesSidebar />
          </Suspense>
        </div>
        <div className="md:col-span-3">
          <Suspense fallback={<div>Loading guides...</div>}>
            <StaffGuidesGrid />
          </Suspense>
        </div>
      </div>
    </div>
  );
}
