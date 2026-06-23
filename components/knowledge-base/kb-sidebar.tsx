"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Folder } from "lucide-react";
import Link from "next/link";
import { fetchApi } from "@/lib/api/client";

type Category = {
  id: string;
  name: string;
  articleCount?: number;
};

export function KnowledgeBaseSidebar() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<Category[]>("/api/knowledge-base/categories", undefined, {
          fallbackMessage: "Failed to load categories",
        });
        setCategories(data);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Categories</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {loading ? (
          <p className="text-sm text-muted-foreground">Loading...</p>
        ) : categories.length === 0 ? (
          <p className="text-sm text-muted-foreground">No categories yet</p>
        ) : (
          <>
            <Link href="/knowledge-base">
              <div className="flex items-center justify-between p-2 rounded-md hover:bg-accent transition-colors cursor-pointer">
                <span className="text-sm font-medium">All Articles</span>
              </div>
            </Link>
            {categories.map((category) => (
              <Link key={category.id} href={`/knowledge-base?category=${category.id}`}>
                <div className="flex items-center justify-between p-2 rounded-md hover:bg-accent transition-colors cursor-pointer">
                  <div className="flex items-center gap-2">
                    <Folder className="h-4 w-4 text-muted-foreground" />
                    <span className="text-sm">{category.name}</span>
                  </div>
                  <Badge variant="secondary">{category.articleCount ?? 0}</Badge>
                </div>
              </Link>
            ))}
          </>
        )}
      </CardContent>
    </Card>
  );
}
