"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Megaphone,
  Users,
  CalendarDays,
  Share2,
  TrendingUp,
  Globe,
  CheckCircle2,
  XCircle,
  Loader2,
  ArrowRight,
} from "lucide-react";
import Link from "next/link";
import { ComponentType } from "react";

interface MarketingFeature {
  name: string;
  displayName: string;
  description: string;
  isEnabled: boolean;
  itemCount: number;
  config: Record<string, any> | null;
}

interface MarketingFeaturesResponse {
  clientId: string;
  features: MarketingFeature[];
  isStaff: boolean;
}

const featureIcons: Record<string, ComponentType<{ className?: string }>> = {
  marketing_tools: Megaphone,
  lead_management: Users,
  content_calendar: CalendarDays,
  social_media: Share2,
  ad_management: TrendingUp,
  brand_monitoring: Globe,
};

const featureLinks: Record<string, string> = {
  marketing_tools: "/marketing/campaigns",
  lead_management: "/marketing/leads",
  content_calendar: "/marketing/content-calendar",
  social_media: "/social-media",
  ad_management: "/ads",
  brand_monitoring: "/brand/monitoring",
};

interface MarketingOverviewProps {
  clientId?: string;
}

export function MarketingOverview({ clientId }: MarketingOverviewProps) {
  const [data, setData] = useState<MarketingFeaturesResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchFeatures() {
      try {
        const params = clientId ? `?client_id=${clientId}` : "";
        const res = await fetch(`/api/marketing/features${params}`);
        if (!res.ok) throw new Error("Failed to load marketing features");
        const json = await res.json();
        setData(json);
      } catch (err: any) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }
    fetchFeatures();
  }, [clientId]);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <Card>
        <CardContent className="py-8 text-center text-muted-foreground">
          {error}
        </CardContent>
      </Card>
    );
  }

  if (!data) return null;

  const enabledFeatures = data.features.filter((f) => f.isEnabled);
  const disabledFeatures = data.features.filter((f) => !f.isEnabled);

  return (
    <div className="space-y-8">
      {/* Active Marketing Services */}
      <div>
        <h2 className="mb-4 text-lg font-semibold">Active Marketing Services</h2>
        {enabledFeatures.length === 0 ? (
          <Card>
            <CardContent className="py-8 text-center text-muted-foreground">
              No marketing services are currently enabled for this account.
              {data.isStaff && " Enable features from the admin panel."}
            </CardContent>
          </Card>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {enabledFeatures.map((feature) => {
              const Icon = featureIcons[feature.name] || Megaphone;
              const href = featureLinks[feature.name];
              return (
                <Card key={feature.name} className="relative overflow-hidden">
                  <div className="absolute inset-x-0 top-0 h-1 bg-primary" />
                  <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                          <Icon className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                          <CardTitle className="text-base">{feature.displayName}</CardTitle>
                        </div>
                      </div>
                      <Badge variant="default" className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <CheckCircle2 className="mr-1 h-3 w-3" />
                        Active
                      </Badge>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <CardDescription className="mb-3">{feature.description}</CardDescription>
                    {feature.itemCount > 0 && (
                      <p className="mb-3 text-sm font-medium">
                        {feature.itemCount} {feature.itemCount === 1 ? "item" : "items"}
                      </p>
                    )}
                    {data.isStaff && href && (
                      <Link href={href}>
                        <Button variant="outline" size="sm" className="w-full">
                          Manage
                          <ArrowRight className="ml-2 h-3 w-3" />
                        </Button>
                      </Link>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </div>

      {/* Unavailable Services */}
      {disabledFeatures.length > 0 && (
        <div>
          <h2 className="mb-4 text-lg font-semibold text-muted-foreground">
            Available Marketing Services
          </h2>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {disabledFeatures.map((feature) => {
              const Icon = featureIcons[feature.name] || Megaphone;
              return (
                <Card key={feature.name} className="opacity-60">
                  <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                          <Icon className="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                          <CardTitle className="text-base">{feature.displayName}</CardTitle>
                        </div>
                      </div>
                      <Badge variant="secondary">
                        <XCircle className="mr-1 h-3 w-3" />
                        Not Active
                      </Badge>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <CardDescription>{feature.description}</CardDescription>
                    {!data.isStaff && (
                      <p className="mt-2 text-xs text-muted-foreground">
                        Contact your account manager to enable this service.
                      </p>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
