"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Lock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { fetchApi } from "@/lib/api/client";

type GuideDetail = {
  id: string;
  title: string;
  summary: string | null;
  content: string;
  categoryName: string;
  serviceTier: string | null;
  price: string | number | null;
  commitment: string | null;
  isInternal: boolean;
  isPublished: boolean;
  viewCount: number;
};

export function StaffGuideDetail({ guideId }: { guideId: string }) {
  const [guide, setGuide] = useState<GuideDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<GuideDetail>(`/api/staff-guides/${guideId}`, undefined, {
          fallbackMessage: "Failed to load guide",
        });
        setGuide(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load guide");
      } finally {
        setLoading(false);
      }
    })();
  }, [guideId]);

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading guide...</div>;
  }

  if (error || !guide) {
    return <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error ?? "Guide not found"}</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Button variant="outline" asChild>
          <Link href="/staff-guides">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Staff Guides
          </Link>
        </Button>
        <div className="flex gap-2">
          {guide.isInternal && (
            <Badge variant="secondary">
              <Lock className="h-3 w-3 mr-1" />
              Internal
            </Badge>
          )}
          {!guide.isPublished && <Badge variant="secondary">Draft</Badge>}
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center gap-2 mb-2">
            <Badge variant="outline">{guide.categoryName}</Badge>
            {guide.serviceTier && <Badge variant="outline">{guide.serviceTier}</Badge>}
            {guide.price != null && <Badge variant="outline">${guide.price}</Badge>}
            {guide.commitment && <Badge variant="outline">{guide.commitment}</Badge>}
          </div>
          <CardTitle className="text-2xl">{guide.title}</CardTitle>
          {guide.summary ? <p className="text-muted-foreground">{guide.summary}</p> : null}
        </CardHeader>
        <CardContent>
          <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap">{guide.content}</div>
          <p className="mt-6 text-xs text-muted-foreground">{guide.viewCount} views</p>
        </CardContent>
      </Card>
    </div>
  );
}
