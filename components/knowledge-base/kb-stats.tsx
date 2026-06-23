"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, Eye, ThumbsUp, Folder } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

type Article = {
  isPublished: boolean;
  viewCount: number;
  helpfulCount: number;
};

type Category = {
  id: string;
};

export function KnowledgeBaseStats() {
  const [stats, setStats] = useState({
    totalArticles: 0,
    published: 0,
    categories: 0,
    totalViews: 0,
    helpfulCount: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const [articles, categories] = await Promise.all([
          fetchApi<Article[]>("/api/knowledge-base/articles", undefined, { fallbackMessage: "Failed to load articles" }),
          fetchApi<Category[]>("/api/knowledge-base/categories", undefined, { fallbackMessage: "Failed to load categories" }),
        ]);

        setStats({
          totalArticles: articles.length,
          published: articles.filter((a) => a.isPublished).length,
          categories: categories.length,
          totalViews: articles.reduce((sum, a) => sum + (a.viewCount ?? 0), 0),
          helpfulCount: articles.reduce((sum, a) => sum + (a.helpfulCount ?? 0), 0),
        });
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading stats...</div>;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Articles</CardTitle>
          <FileText className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalArticles}</div>
          <p className="text-xs text-muted-foreground">{stats.published} published</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Categories</CardTitle>
          <Folder className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.categories}</div>
          <p className="text-xs text-muted-foreground">Organized categories</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Views</CardTitle>
          <Eye className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalViews.toLocaleString()}</div>
          <p className="text-xs text-muted-foreground">All time views</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Helpful Votes</CardTitle>
          <ThumbsUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.helpfulCount}</div>
          <p className="text-xs text-muted-foreground">Positive feedback</p>
        </CardContent>
      </Card>
    </div>
  );
}
