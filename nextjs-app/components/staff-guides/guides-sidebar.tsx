"use client";

import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Folder } from "lucide-react";
import Link from "next/link";

type Category = {
  id: string;
  name: string;
  guideCount: number;
};

export function StaffGuidesSidebar() {
  const [categories] = useState<Category[]>([]);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Categories</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {categories.length === 0 ? (
          <p className="text-sm text-muted-foreground">No categories yet</p>
        ) : (
          categories.map((category) => (
            <Link key={category.id} href={`/staff-guides?category=${category.id}`}>
              <div className="flex items-center justify-between p-2 rounded-md hover:bg-accent transition-colors cursor-pointer">
                <div className="flex items-center gap-2">
                  <Folder className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm">{category.name}</span>
                </div>
                <Badge variant="secondary">{category.guideCount}</Badge>
              </div>
            </Link>
          ))
        )}
      </CardContent>
    </Card>
  );
}
