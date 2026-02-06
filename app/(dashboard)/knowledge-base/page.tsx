import { Suspense } from "react";
import { KnowledgeBaseGrid } from "@/components/knowledge-base/kb-grid";
import { KnowledgeBaseStats } from "@/components/knowledge-base/kb-stats";
import { KnowledgeBaseSidebar } from "@/components/knowledge-base/kb-sidebar";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Knowledge Base | Dashboard",
  description: "Client-facing help articles and documentation",
};

export default function KnowledgeBasePage() {
  return (
    <div className="container mx-auto py-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Knowledge Base</h1>
          <p className="text-muted-foreground mt-1">Client-facing help articles and documentation</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" asChild>
            <Link href="/knowledge-base/categories">Manage Categories</Link>
          </Button>
          <Button asChild>
            <Link href="/knowledge-base/articles/new">
              <Plus className="mr-2 h-4 w-4" />
              New Article
            </Link>
          </Button>
        </div>
      </div>

      <Suspense fallback={<div>Loading stats...</div>}>
        <KnowledgeBaseStats />
      </Suspense>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <div className="md:col-span-1">
          <Suspense fallback={<div>Loading categories...</div>}>
            <KnowledgeBaseSidebar />
          </Suspense>
        </div>
        <div className="md:col-span-3">
          <Suspense fallback={<div>Loading articles...</div>}>
            <KnowledgeBaseGrid />
          </Suspense>
        </div>
      </div>
    </div>
  );
}
