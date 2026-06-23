"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, Eye, Lock, Folder } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

type Guide = {
  isPublished: boolean;
  isInternal: boolean;
  viewCount: number;
};

type Category = {
  id: string;
};

export function StaffGuidesStats() {
  const [stats, setStats] = useState({
    totalGuides: 0,
    published: 0,
    internal: 0,
    categories: 0,
    totalViews: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const [guides, categories] = await Promise.all([
          fetchApi<Guide[]>("/api/staff-guides", undefined, { fallbackMessage: "Failed to load guides" }),
          fetchApi<Category[]>("/api/staff-guides/categories", undefined, { fallbackMessage: "Failed to load categories" }),
        ]);

        setStats({
          totalGuides: guides.length,
          published: guides.filter((g) => g.isPublished).length,
          internal: guides.filter((g) => g.isInternal).length,
          categories: categories.length,
          totalViews: guides.reduce((sum, g) => sum + (g.viewCount ?? 0), 0),
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
          <CardTitle className="text-sm font-medium">Total Guides</CardTitle>
          <FileText className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalGuides}</div>
          <p className="text-xs text-muted-foreground">{stats.published} published</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Internal Guides</CardTitle>
          <Lock className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.internal}</div>
          <p className="text-xs text-muted-foreground">Staff-only access</p>
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
    </div>
  );
}
