"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { CalendarIcon, ArrowLeft, Loader2 } from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import Link from "next/link";
import { createClient } from "@/lib/supabase/client";

const adCampaignSchema = z.object({
  name: z.string().min(1, "Campaign name is required"),
  adAccountId: z.string().min(1, "Ad account is required"),
  objective: z.string().min(1, "Campaign objective is required"),
  status: z.enum(["draft", "active", "paused", "completed"]).default("draft"),
  budget: z.string().optional(),
  budgetType: z.enum(["daily", "lifetime"]).default("daily"),
  startDate: z.date().optional(),
  endDate: z.date().optional(),
  targetingDescription: z.string().optional(),
});

type AdCampaignFormData = z.infer<typeof adCampaignSchema>;

export default function NewAdCampaignPage() {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [adAccounts, setAdAccounts] = useState<Array<{ id: string; platform: string; account_name: string }>>([]);
  const supabase = createClient();

  const form = useForm<AdCampaignFormData>({
    resolver: zodResolver(adCampaignSchema),
    defaultValues: {
      name: "",
      objective: "",
      status: "draft",
      budgetType: "daily",
    },
  });

  useEffect(() => {
    async function fetchAdAccounts() {
      const { data } = await supabase.from("ad_accounts").select("id, platform, account_name").is("deleted_at", null).order("platform");
      if (data) setAdAccounts(data);
    }
    fetchAdAccounts();
  }, []);

  async function onSubmit(data: AdCampaignFormData) {
    setIsSubmitting(true);

    try {
      const response = await fetch("/api/ads/campaigns", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: data.name,
          ad_account_id: data.adAccountId,
          objective: data.objective,
          status: data.status,
          budget: data.budget ? parseFloat(data.budget) : null,
          budget_type: data.budgetType,
          start_date: data.startDate?.toISOString(),
          end_date: data.endDate?.toISOString(),
          metadata: {
            targeting_description: data.targetingDescription,
          },
        }),
      });

      if (!response.ok) {
        throw new Error("Failed to create ad campaign");
      }

      toast.success("Ad campaign created successfully!");
      router.push("/ads");
      router.refresh();
    } catch (error) {
      console.error("Error creating ad campaign:", error);
      toast.error("Failed to create ad campaign");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="container mx-auto p-6 max-w-4xl">
      <div className="mb-6">
        <Link href="/ads">
          <Button variant="ghost" size="sm" className="gap-2 mb-4">
            <ArrowLeft className="h-4 w-4" />
            Back to Ad Campaigns
          </Button>
        </Link>
        <h1 className="text-3xl font-bold tracking-tight">Create Ad Campaign</h1>
        <p className="text-muted-foreground mt-2">Set up a new advertising campaign on Facebook, Google, or other platforms</p>
      </div>

      {adAccounts.length === 0 && (
        <Card className="mb-6 border-yellow-500/50 bg-yellow-500/5">
          <CardHeader>
            <CardTitle className="text-yellow-600">No Ad Accounts Connected</CardTitle>
            <CardDescription>You need to connect an ad account before creating campaigns. Go to Settings to connect your Facebook Ads or Google Ads account.</CardDescription>
          </CardHeader>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Campaign Details</CardTitle>
          <CardDescription>Configure your advertising campaign settings</CardDescription>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              {/* Campaign Name */}
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Campaign Name *</FormLabel>
                    <FormControl>
                      <Input placeholder="e.g., Summer Sale 2024 - Facebook Ads" {...field} />
                    </FormControl>
                    <FormDescription>A descriptive name for this ad campaign</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Ad Account */}
              <FormField
                control={form.control}
                name="adAccountId"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Ad Account *</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select ad account" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {adAccounts.length === 0 ? (
                          <SelectItem value="none" disabled>
                            No ad accounts available
                          </SelectItem>
                        ) : (
                          adAccounts.map((account) => (
                            <SelectItem key={account.id} value={account.id}>
                              {account.platform} - {account.account_name}
                            </SelectItem>
                          ))
                        )}
                      </SelectContent>
                    </Select>
                    <FormDescription>Which ad platform to use</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Campaign Objective */}
                <FormField
                  control={form.control}
                  name="objective"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Campaign Objective *</FormLabel>
                      <Select onValueChange={field.onChange} value={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="Select objective" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="awareness">Brand Awareness</SelectItem>
                          <SelectItem value="traffic">Website Traffic</SelectItem>
                          <SelectItem value="engagement">Engagement</SelectItem>
                          <SelectItem value="leads">Lead Generation</SelectItem>
                          <SelectItem value="conversions">Conversions</SelectItem>
                          <SelectItem value="sales">Sales</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Status */}
                <FormField
                  control={form.control}
                  name="status"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Status</FormLabel>
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="draft">Draft</SelectItem>
                          <SelectItem value="active">Active</SelectItem>
                          <SelectItem value="paused">Paused</SelectItem>
                          <SelectItem value="completed">Completed</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Budget */}
                <FormField
                  control={form.control}
                  name="budget"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Budget</FormLabel>
                      <FormControl>
                        <Input type="number" step="0.01" placeholder="e.g., 500.00" {...field} />
                      </FormControl>
                      <FormDescription>Campaign budget amount</FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Budget Type */}
                <FormField
                  control={form.control}
                  name="budgetType"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Budget Type</FormLabel>
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="daily">Daily Budget</SelectItem>
                          <SelectItem value="lifetime">Lifetime Budget</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Start Date */}
                <FormField
                  control={form.control}
                  name="startDate"
                  render={({ field }) => (
                    <FormItem className="flex flex-col">
                      <FormLabel>Start Date</FormLabel>
                      <Popover>
                        <PopoverTrigger asChild>
                          <FormControl>
                            <Button variant="outline" className={cn("w-full pl-3 text-left font-normal", !field.value && "text-muted-foreground")}>
                              {field.value ? format(field.value, "PPP") : <span>Pick a date</span>}
                              <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                            </Button>
                          </FormControl>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                          <Calendar mode="single" selected={field.value} onSelect={field.onChange} initialFocus />
                        </PopoverContent>
                      </Popover>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* End Date */}
                <FormField
                  control={form.control}
                  name="endDate"
                  render={({ field }) => (
                    <FormItem className="flex flex-col">
                      <FormLabel>End Date</FormLabel>
                      <Popover>
                        <PopoverTrigger asChild>
                          <FormControl>
                            <Button variant="outline" className={cn("w-full pl-3 text-left font-normal", !field.value && "text-muted-foreground")}>
                              {field.value ? format(field.value, "PPP") : <span>Pick a date</span>}
                              <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                            </Button>
                          </FormControl>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                          <Calendar mode="single" selected={field.value} onSelect={field.onChange} initialFocus />
                        </PopoverContent>
                      </Popover>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              {/* Targeting */}
              <FormField
                control={form.control}
                name="targetingDescription"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Targeting & Audience</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="Describe your target audience (age, location, interests, behaviors...)"
                        className="min-h-[100px]"
                        {...field}
                      />
                    </FormControl>
                    <FormDescription>Details about who you want to reach with this campaign</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* Action Buttons */}
              <div className="flex gap-4 pt-4">
                <Button type="submit" disabled={isSubmitting || adAccounts.length === 0}>
                  {isSubmitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Creating...
                    </>
                  ) : (
                    "Create Ad Campaign"
                  )}
                </Button>
                <Button type="button" variant="outline" onClick={() => router.back()} disabled={isSubmitting}>
                  Cancel
                </Button>
              </div>
            </form>
          </Form>
        </CardContent>
      </Card>
    </div>
  );
}
