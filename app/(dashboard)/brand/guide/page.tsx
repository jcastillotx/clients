import { Suspense } from "react";
import { BrandGuideBuilder } from "@/components/brand/brand-guide-builder";
import { BrandGuideList } from "@/components/brand/brand-guide-list";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata = {
  title: "Brand Guide Builder",
  description: "Create and manage your brand guidelines",
};

function BrandGuideLoading() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-8 w-64" />
      <Skeleton className="h-64 w-full" />
    </div>
  );
}

export default function BrandGuidePage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex flex-col gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Brand Guide Builder</h1>
          <p className="text-muted-foreground">
            Create comprehensive brand guidelines with colors, typography, and assets
          </p>
        </div>

        <Tabs defaultValue="builder" className="w-full">
          <TabsList>
            <TabsTrigger value="builder">Builder</TabsTrigger>
            <TabsTrigger value="guides">All Guides</TabsTrigger>
          </TabsList>

          <TabsContent value="builder" className="space-y-4">
            <Suspense fallback={<BrandGuideLoading />}>
              <BrandGuideBuilder />
            </Suspense>
          </TabsContent>

          <TabsContent value="guides" className="space-y-4">
            <Suspense fallback={<BrandGuideLoading />}>
              <BrandGuideList />
            </Suspense>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
