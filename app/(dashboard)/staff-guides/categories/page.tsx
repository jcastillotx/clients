import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { StaffGuideCategoryManager } from "@/components/staff-guides/guide-category-manager";

export const metadata = {
  title: "Staff Guide Categories | Dashboard",
  description: "Manage staff guide categories",
};

export default function StaffGuideCategoriesPage() {
  return (
    <div className="container mx-auto max-w-5xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Staff Guide Categories</h1>
          <p className="text-muted-foreground mt-1">Create and manage internal guide categories.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/staff-guides">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Staff Guides
          </Link>
        </Button>
      </div>
      <StaffGuideCategoryManager />
    </div>
  );
}
