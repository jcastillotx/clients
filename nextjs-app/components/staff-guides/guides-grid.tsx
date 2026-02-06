"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Eye, Lock, DollarSign } from "lucide-react";
import Link from "next/link";

type Guide = {
  id: string;
  title: string;
  summary: string;
  categoryName: string;
  serviceTier?: string;
  price?: string;
  viewCount: number;
  isInternal: boolean;
  isPublished: boolean;
};

export function StaffGuidesGrid() {
  const [guides] = useState<Guide[]>([]);

  return (
    <div className="space-y-4">
      {guides.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-muted-foreground">
            No guides found. Create your first staff guide to get started.
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {guides.map((guide) => (
            <Link key={guide.id} href={`/staff-guides/${guide.id}`}>
              <Card className="h-full hover:bg-accent transition-colors cursor-pointer">
                <CardHeader>
                  <div className="flex items-start justify-between gap-2">
                    <Badge variant="outline" className="mb-2">
                      {guide.categoryName}
                    </Badge>
                    <div className="flex gap-1">
                      {guide.isInternal && (
                        <Badge variant="secondary">
                          <Lock className="h-3 w-3 mr-1" />
                          Internal
                        </Badge>
                      )}
                      {!guide.isPublished && <Badge variant="secondary">Draft</Badge>}
                    </div>
                  </div>
                  <CardTitle className="text-lg">{guide.title}</CardTitle>
                  <CardDescription className="line-clamp-2">{guide.summary}</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                      <div className="flex items-center gap-1">
                        <Eye className="h-4 w-4" />
                        <span>{guide.viewCount}</span>
                      </div>
                      {guide.serviceTier && <Badge variant="outline">{guide.serviceTier}</Badge>}
                    </div>
                    {guide.price && (
                      <div className="flex items-center gap-1 text-sm font-medium">
                        <DollarSign className="h-4 w-4" />
                        <span>{guide.price}</span>
                      </div>
                    )}
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
