"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";

const competitorSchema = z.object({
  competitorName: z.string().min(1, "Competitor name is required"),
  websiteUrl: z.string().url().optional().or(z.literal("")),
  positioning: z.string().optional(),
  targetAudience: z.string().optional(),
  keyDifferentiators: z.string().optional(),
  isActive: z.boolean().default(true),
  facebookUrl: z.string().url().optional().or(z.literal("")),
  twitterUrl: z.string().url().optional().or(z.literal("")),
  linkedinUrl: z.string().url().optional().or(z.literal("")),
  instagramUrl: z.string().url().optional().or(z.literal("")),
  strengths: z.string().optional(),
  weaknesses: z.string().optional(),
  marketShare: z.string().optional(),
});

type CompetitorFormValues = z.infer<typeof competitorSchema>;

interface AddCompetitorDialogProps {
  children: React.ReactNode;
}

export function AddCompetitorDialog({ children }: AddCompetitorDialogProps) {
  const [open, setOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const form = useForm<CompetitorFormValues>({
    resolver: zodResolver(competitorSchema),
    defaultValues: {
      isActive: true,
    },
  });

  const onSubmit = async (data: CompetitorFormValues) => {
    setIsLoading(true);
    try {
      const payload = {
        competitorName: data.competitorName,
        websiteUrl: data.websiteUrl || null,
        positioning: data.positioning || null,
        targetAudience: data.targetAudience || null,
        keyDifferentiators: data.keyDifferentiators ? data.keyDifferentiators.split(",").map((s) => s.trim()) : null,
        isActive: data.isActive,
        meta: {
          socialLinks: {
            ...(data.facebookUrl && { facebook: data.facebookUrl }),
            ...(data.twitterUrl && { twitter: data.twitterUrl }),
            ...(data.linkedinUrl && { linkedin: data.linkedinUrl }),
            ...(data.instagramUrl && { instagram: data.instagramUrl }),
          },
          strengths: data.strengths ? data.strengths.split(",").map((s) => s.trim()) : null,
          weaknesses: data.weaknesses ? data.weaknesses.split(",").map((s) => s.trim()) : null,
          marketShare: data.marketShare ? parseFloat(data.marketShare) : null,
        },
      };

      const response = await fetch("/api/brand/competitors", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (!response.ok) throw new Error("Failed to add competitor");

      toast.success("Competitor added successfully");
      form.reset();
      setOpen(false);
    } catch (error) {
      toast.error("Failed to add competitor");
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add Competitor</DialogTitle>
          <DialogDescription>Add a new competitor to track and analyze</DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="competitorName"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Competitor Name</FormLabel>
                  <FormControl>
                    <Input placeholder="Acme Corp" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="websiteUrl"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Website URL</FormLabel>
                  <FormControl>
                    <Input placeholder="https://example.com" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="positioning"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Positioning</FormLabel>
                  <FormControl>
                    <Textarea placeholder="How they position themselves in the market" {...field} rows={3} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="targetAudience"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Target Audience</FormLabel>
                  <FormControl>
                    <Textarea placeholder="Who they target" {...field} rows={2} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="keyDifferentiators"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Key Differentiators</FormLabel>
                  <FormControl>
                    <Input placeholder="Comma-separated list" {...field} />
                  </FormControl>
                  <FormDescription>What makes them unique (comma-separated)</FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="facebookUrl"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Facebook</FormLabel>
                    <FormControl>
                      <Input placeholder="https://facebook.com/..." {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="twitterUrl"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Twitter/X</FormLabel>
                    <FormControl>
                      <Input placeholder="https://twitter.com/..." {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="linkedinUrl"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>LinkedIn</FormLabel>
                    <FormControl>
                      <Input placeholder="https://linkedin.com/..." {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="instagramUrl"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Instagram</FormLabel>
                    <FormControl>
                      <Input placeholder="https://instagram.com/..." {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="strengths"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Strengths</FormLabel>
                  <FormControl>
                    <Input placeholder="Comma-separated list" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="weaknesses"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Weaknesses</FormLabel>
                  <FormControl>
                    <Input placeholder="Comma-separated list" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="marketShare"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Market Share (%)</FormLabel>
                  <FormControl>
                    <Input type="number" step="0.1" placeholder="15.5" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="isActive"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">Active Monitoring</FormLabel>
                    <FormDescription>Track this competitor actively</FormDescription>
                  </div>
                  <FormControl>
                    <Switch checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                </FormItem>
              )}
            />

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={isLoading}>
                {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Add Competitor
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
