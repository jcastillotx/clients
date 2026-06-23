import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { StaffGuideForm } from "@/components/staff-guides/guide-form";

export const metadata = {
  title: "New Staff Guide | Dashboard",
  description: "Create an internal staff guide",
};

export default function NewStaffGuidePage() {
  return (
    <div className="container mx-auto max-w-4xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">New Staff Guide</h1>
          <p className="text-muted-foreground mt-1">Create internal SOPs, training, or service documentation.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/staff-guides">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Staff Guides
          </Link>
        </Button>
      </div>
      <StaffGuideForm />
    </div>
  );
}
