import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { KbArticleForm } from "@/components/knowledge-base/kb-article-form";

export const metadata = {
  title: "New Article | Knowledge Base",
  description: "Create a knowledge base article",
};

export default function NewKbArticlePage() {
  return (
    <div className="container mx-auto max-w-4xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">New Article</h1>
          <p className="text-muted-foreground mt-1">Write a client-facing help article.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/knowledge-base">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Knowledge Base
          </Link>
        </Button>
      </div>
      <KbArticleForm />
    </div>
  );
}
