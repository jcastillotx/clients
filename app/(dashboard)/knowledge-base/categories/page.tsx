import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { KbCategoryManager } from "@/components/knowledge-base/kb-category-manager";

export const metadata = {
  title: "KB Categories | Dashboard",
  description: "Manage knowledge base categories",
};

export default function KbCategoriesPage() {
  return (
    <div className="container mx-auto max-w-5xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Knowledge Base Categories</h1>
          <p className="text-muted-foreground mt-1">Create and manage article categories.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/knowledge-base">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Knowledge Base
          </Link>
        </Button>
      </div>
      <KbCategoryManager />
    </div>
  );
}
