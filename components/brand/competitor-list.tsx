"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ExternalLink, Edit, Trash2, TrendingUp, Users, Target } from "lucide-react";
import { toast } from "sonner";

interface BrandCompetitor {
  id: string;
  competitorName: string;
  websiteUrl: string | null;
  positioning: string | null;
  targetAudience: string | null;
  keyDifferentiators: string[] | null;
  isActive: boolean;
  meta: {
    socialLinks?: {
      facebook?: string;
      twitter?: string;
      linkedin?: string;
      instagram?: string;
    };
    strengths?: string[];
    weaknesses?: string[];
    marketShare?: number;
  } | null;
  createdAt: string;
  updatedAt: string;
}

interface CompetitorListProps {
  activeOnly?: boolean;
}

export function CompetitorList({ activeOnly = false }: CompetitorListProps) {
  const [competitors, setCompetitors] = useState<BrandCompetitor[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchCompetitors();
  }, [activeOnly]);

  const fetchCompetitors = async () => {
    try {
      const params = new URLSearchParams();
      if (activeOnly) params.append("active", "true");

      const response = await fetch(`/api/brand/competitors?${params}`);
      if (!response.ok) throw new Error("Failed to fetch competitors");
      const data = await response.json();
      setCompetitors(data);
    } catch (error) {
      console.error("Failed to fetch competitors:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const toggleActive = async (id: string, isActive: boolean) => {
    try {
      const response = await fetch(`/api/brand/competitors/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ isActive: !isActive }),
      });

      if (!response.ok) throw new Error("Failed to update competitor");

      toast.success(`Competitor ${!isActive ? "activated" : "deactivated"}`);
      fetchCompetitors();
    } catch (error) {
      toast.error("Failed to update competitor");
      console.error(error);
    }
  };

  const deleteCompetitor = async (id: string) => {
    if (!confirm("Are you sure you want to delete this competitor?")) return;

    try {
      const response = await fetch(`/api/brand/competitors/${id}`, {
        method: "DELETE",
      });

      if (!response.ok) throw new Error("Failed to delete competitor");

      toast.success("Competitor deleted");
      fetchCompetitors();
    } catch (error) {
      toast.error("Failed to delete competitor");
      console.error(error);
    }
  };

  if (isLoading) {
    return <div>Loading competitors...</div>;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {competitors.map((competitor) => (
        <Card key={competitor.id}>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div>
                <CardTitle className="text-lg">{competitor.competitorName}</CardTitle>
                <CardDescription>{competitor.positioning || "No positioning defined"}</CardDescription>
              </div>
              <Badge variant={competitor.isActive ? "default" : "secondary"}>
                {competitor.isActive ? "Active" : "Inactive"}
              </Badge>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {competitor.targetAudience && (
              <div className="flex items-start gap-2">
                <Users className="h-4 w-4 text-muted-foreground mt-0.5" />
                <div>
                  <p className="text-sm font-medium">Target Audience</p>
                  <p className="text-sm text-muted-foreground">{competitor.targetAudience}</p>
                </div>
              </div>
            )}

            {competitor.keyDifferentiators && competitor.keyDifferentiators.length > 0 && (
              <div className="flex items-start gap-2">
                <Target className="h-4 w-4 text-muted-foreground mt-0.5" />
                <div>
                  <p className="text-sm font-medium">Key Differentiators</p>
                  <div className="flex flex-wrap gap-1 mt-1">
                    {competitor.keyDifferentiators.map((diff, index) => (
                      <Badge key={index} variant="outline" className="text-xs">
                        {diff}
                      </Badge>
                    ))}
                  </div>
                </div>
              </div>
            )}

            {competitor.meta?.marketShare && (
              <div className="flex items-start gap-2">
                <TrendingUp className="h-4 w-4 text-muted-foreground mt-0.5" />
                <div>
                  <p className="text-sm font-medium">Market Share</p>
                  <p className="text-sm text-muted-foreground">{competitor.meta.marketShare}%</p>
                </div>
              </div>
            )}

            {competitor.meta?.strengths && competitor.meta.strengths.length > 0 && (
              <div>
                <p className="text-sm font-medium mb-1">Strengths</p>
                <ul className="text-sm text-muted-foreground list-disc list-inside space-y-1">
                  {competitor.meta.strengths.slice(0, 3).map((strength, index) => (
                    <li key={index}>{strength}</li>
                  ))}
                </ul>
              </div>
            )}

            <div className="flex gap-2 pt-2">
              {competitor.websiteUrl && (
                <Button size="sm" variant="outline" asChild>
                  <a href={competitor.websiteUrl} target="_blank" rel="noopener noreferrer">
                    <ExternalLink className="mr-1 h-4 w-4" />
                    Visit
                  </a>
                </Button>
              )}
              <Button size="sm" variant="outline">
                <Edit className="mr-1 h-4 w-4" />
                Edit
              </Button>
              <Button size="sm" variant="outline" onClick={() => toggleActive(competitor.id, competitor.isActive)}>
                {competitor.isActive ? "Deactivate" : "Activate"}
              </Button>
              <Button size="sm" variant="outline" onClick={() => deleteCompetitor(competitor.id)}>
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>

            {competitor.meta?.socialLinks && (
              <div className="flex gap-2 pt-2">
                {Object.entries(competitor.meta.socialLinks).map(([platform, url]) => (
                  <Button key={platform} size="sm" variant="ghost" asChild>
                    <a href={url} target="_blank" rel="noopener noreferrer" className="capitalize">
                      {platform}
                    </a>
                  </Button>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
