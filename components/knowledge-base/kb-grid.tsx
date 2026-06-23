"use client";

import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Eye, ThumbsUp } from "lucide-react";
import Link from "next/link";
import { fetchApi } from "@/lib/api/client";

type Article = {
  id: string;
  title: string;
  excerpt: string | null;
  categoryName: string;
  viewCount: number;
  helpfulCount: number;
  isPublished: boolean;
};

export function KnowledgeBaseGrid() {
  const searchParams = useSearchParams();
  const categoryId = searchParams.get("category");
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);

  const loadArticles = useCallback(async () => {
    setLoading(true);
    try {
      const url = categoryId
        ? `/api/knowledge-base/articles?categoryId=${encodeURIComponent(categoryId)}`
        : "/api/knowledge-base/articles";
      const data = await fetchApi<Article[]>(url, undefined, { fallbackMessage: "Failed to load articles" });
      setArticles(data);
    } catch {
      setArticles([]);
    } finally {
      setLoading(false);
    }
  }, [categoryId]);

  useEffect(() => {
    void loadArticles();
  }, [loadArticles]);

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading articles...</div>;
  }

  return (
    <div className="space-y-4">
      {articles.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-muted-foreground">
            No articles found. Create your first article to get started.
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {articles.map((article) => (
            <Link key={article.id} href={`/knowledge-base/articles/${article.id}`}>
              <Card className="h-full hover:bg-accent transition-colors cursor-pointer">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <Badge variant="outline" className="mb-2">
                      {article.categoryName}
                    </Badge>
                    {!article.isPublished && <Badge variant="secondary">Draft</Badge>}
                  </div>
                  <CardTitle className="text-lg">{article.title}</CardTitle>
                  <CardDescription className="line-clamp-2">{article.excerpt ?? "No excerpt"}</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center gap-4 text-sm text-muted-foreground">
                    <div className="flex items-center gap-1">
                      <Eye className="h-4 w-4" />
                      <span>{article.viewCount}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <ThumbsUp className="h-4 w-4" />
                      <span>{article.helpfulCount}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
