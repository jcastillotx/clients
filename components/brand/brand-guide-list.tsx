"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Eye, Edit, Trash2, Copy, Lock } from "lucide-react";
import { formatDistanceToNow } from "date-fns";

interface BrandGuide {
  id: string;
  slug: string;
  status: string;
  coverImage?: string;
  isPublic: boolean;
  passwordProtected: boolean;
  version: number;
  createdAt: string;
  updatedAt: string;
}

export function BrandGuideList() {
  const [guides, setGuides] = useState<BrandGuide[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchGuides();
  }, []);

  const fetchGuides = async () => {
    try {
      const response = await fetch("/api/brand/guides");
      if (!response.ok) throw new Error("Failed to fetch guides");
      const data = await response.json();
      setGuides(data);
    } catch (error) {
      console.error("Failed to fetch guides:", error);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {guides.map((guide) => (
        <Card key={guide.id}>
          {guide.coverImage && (
            <div className="aspect-video w-full overflow-hidden rounded-t-lg">
              <img src={guide.coverImage} alt={guide.slug} className="h-full w-full object-cover" />
            </div>
          )}
          <CardHeader>
            <div className="flex items-start justify-between">
              <CardTitle className="text-lg">{guide.slug}</CardTitle>
              <Badge variant={guide.status === "published" ? "default" : "secondary"}>{guide.status}</Badge>
            </div>
            <CardDescription>
              Version {guide.version} • Updated {formatDistanceToNow(new Date(guide.updatedAt), { addSuffix: true })}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-2 mb-4">
              {guide.isPublic && <Badge variant="outline">Public</Badge>}
              {guide.passwordProtected && (
                <Badge variant="outline">
                  <Lock className="mr-1 h-3 w-3" />
                  Protected
                </Badge>
              )}
            </div>
            <div className="flex gap-2">
              <Button size="sm" variant="outline">
                <Eye className="mr-1 h-4 w-4" />
                View
              </Button>
              <Button size="sm" variant="outline">
                <Edit className="mr-1 h-4 w-4" />
                Edit
              </Button>
              <Button size="sm" variant="outline">
                <Copy className="mr-1 h-4 w-4" />
                Duplicate
              </Button>
              <Button size="sm" variant="outline">
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
