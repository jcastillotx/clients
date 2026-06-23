"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { fetchApi } from "@/lib/api/client";

type ArticleDetail = {
  id: string;
  title: string;
  excerpt: string | null;
  content: string;
  categoryName: string;
  isPublished: boolean;
  viewCount: number;
  helpfulCount: number;
};

export function KbArticleDetail({ articleId }: { articleId: string }) {
  const [article, setArticle] = useState<ArticleDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<ArticleDetail>(`/api/knowledge-base/articles/${articleId}`, undefined, {
          fallbackMessage: "Failed to load article",
        });
        setArticle(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load article");
      } finally {
        setLoading(false);
      }
    })();
  }, [articleId]);

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading article...</div>;
  }

  if (error || !article) {
    return <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error ?? "Article not found"}</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Button variant="outline" asChild>
          <Link href="/knowledge-base">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Knowledge Base
          </Link>
        </Button>
        {!article.isPublished && <Badge variant="secondary">Draft</Badge>}
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-2 mb-2">
            <Badge variant="outline">{article.categoryName}</Badge>
          </div>
          <CardTitle className="text-2xl">{article.title}</CardTitle>
          {article.excerpt ? <p className="text-muted-foreground">{article.excerpt}</p> : null}
        </CardHeader>
        <CardContent>
          <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap">{article.content}</div>
          <p className="mt-6 text-xs text-muted-foreground">
            {article.viewCount} views · {article.helpfulCount} helpful votes
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
