"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { createSupportTicketSchema, type CreateSupportTicketInput } from "@/lib/validations/support-ticket";
import {
  websiteSupportAffectedAreaOptions,
  websiteSupportPlatformOptions,
  type WebsiteSupportAffectedArea,
  type WebsiteSupportPlatform,
} from "@/lib/support/website-ticket-triage";
import { fetchApi } from "@/lib/api/client";
import { toast } from "sonner";
import { createClient } from "@/lib/supabase/client";
import { X } from "lucide-react";

interface StaffUser {
  id: string;
  name: string;
  email: string;
}

interface EditableSupportTicket {
  subject?: string | null;
  description?: string | null;
  category?: CreateSupportTicketInput["category"] | null;
  priority?: CreateSupportTicketInput["priority"] | null;
  assigned_to?: string | null;
  metadata?: {
    customFields?: {
      requestedDueDate?: string | null;
    } & Record<string, unknown>;
  };
}

interface SupportTicketFormProps {
  staffUsers: StaffUser[];
  ticket?: EditableSupportTicket;
}

export function SupportTicketForm({ staffUsers, ticket }: SupportTicketFormProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [requestedDueDate, setRequestedDueDate] = useState(
    ticket?.metadata?.customFields?.requestedDueDate ? String(ticket.metadata.customFields.requestedDueDate).slice(0, 10) : "",
  );
  const [isWebsiteSupport, setIsWebsiteSupport] = useState(false);
  const [websiteSupport, setWebsiteSupport] = useState({
    clientName: "",
    websiteUrl: "",
    stagingUrl: "",
    affectedPageUrl: "",
    requestedChange: "",
    problemDescription: "",
    deviceAffected: "all",
    browserAffected: "",
    urgency: "",
    businessImpact: "",
    platformBuilder: "WordPress" as WebsiteSupportPlatform,
    affectedAreas: [] as WebsiteSupportAffectedArea[],
    areasNotToChange: "",
  });
  const [uploadedFiles, setUploadedFiles] = useState<Array<{ name: string; url: string; type: string; size: number }>>([]);
  const [isUploading, setIsUploading] = useState(false);
  const supabase = createClient();

  const form = useForm<CreateSupportTicketInput>({
    resolver: zodResolver(createSupportTicketSchema),
    defaultValues: {
      subject: ticket?.subject || "",
      description: ticket?.description || "",
      category: ticket?.category || "general",
      priority: ticket?.priority || "medium",
      assignedTo: ticket?.assigned_to || undefined,
    },
  });

  async function handleFileUpload(event: React.ChangeEvent<HTMLInputElement>) {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    setIsUploading(true);

    try {
      const uploadedUrls: Array<{ name: string; url: string; type: string; size: number }> = [];

      for (const file of Array.from(files)) {
        // Validate file type
        if (!file.type.startsWith("image/")) {
          toast.error(`${file.name} is not an image file`);
          continue;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          toast.error(`${file.name} is too large (max 5MB)`);
          continue;
        }

        // Create unique filename using crypto.randomUUID() for collision resistance
        const fileExt = file.name.split(".").pop();
        const uniqueId = crypto.randomUUID();
        const fileName = `${uniqueId}.${fileExt}`;
        const filePath = `support-tickets/${fileName}`;

        // Upload to Supabase Storage
        const { error } = await supabase.storage.from("attachments").upload(filePath, file, {
          cacheControl: "3600",
          upsert: false,
        });

        if (error) {
          console.error("Upload error:", error);
          toast.error(`Failed to upload ${file.name}`);
          continue;
        }

        // Get public URL
        const {
          data: { publicUrl },
        } = supabase.storage.from("attachments").getPublicUrl(filePath);

        uploadedUrls.push({
          name: file.name,
          url: publicUrl,
          type: file.type,
          size: file.size,
        });
      }

      setUploadedFiles([...uploadedFiles, ...uploadedUrls]);
      toast.success(`${uploadedUrls.length} file(s) uploaded successfully`);

      // Reset file input
      event.target.value = "";
    } catch (error) {
      console.error("Error uploading files:", error);
      toast.error("Failed to upload files");
    } finally {
      setIsUploading(false);
    }
  }

  function removeFile(index: number) {
    setUploadedFiles(uploadedFiles.filter((_, i) => i !== index));
  }

  function updateWebsiteSupportField<K extends keyof typeof websiteSupport>(key: K, value: (typeof websiteSupport)[K]) {
    setWebsiteSupport((current) => ({ ...current, [key]: value }));
  }

  function toggleAffectedArea(area: WebsiteSupportAffectedArea, checked: boolean) {
    setWebsiteSupport((current) => ({
      ...current,
      affectedAreas: checked
        ? Array.from(new Set([...current.affectedAreas, area]))
        : current.affectedAreas.filter((item) => item !== area),
    }));
  }

  async function onSubmit(data: CreateSupportTicketInput) {
    setIsSubmitting(true);

    try {
      const payload: CreateSupportTicketInput = {
        ...data,
        metadata: {
          ...(data.metadata || {}),
          customFields: {
            ...(data.metadata?.customFields || {}),
            requestedDueDate: requestedDueDate || null,
            ...(isWebsiteSupport
              ? {
                  websiteSupport: {
                    isWebsiteSupport: true,
                    ...websiteSupport,
                  },
                }
              : {}),
          },
          attachments: uploadedFiles,
        },
      };

      const ticket = await fetchApi<{ id: string }>(
        "/api/support",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        },
        { fallbackMessage: "Failed to create ticket" },
      );

      if (!ticket.id) {
        throw new Error("Ticket created but response was missing an id");
      }

      toast.success("Support ticket created successfully");
      router.push(`/support/${ticket.id}`);
      router.refresh();
    } catch (error) {
      console.error("Error creating ticket:", error);
      toast.error(error instanceof Error ? error.message : "Failed to create ticket");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Ticket Details</CardTitle>
        <CardDescription>
          Provide details about your support request. Our team will respond according to the priority level.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="subject"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Subject</FormLabel>
                  <FormControl>
                    <Input placeholder="Brief description of the issue" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="Detailed description of the issue, steps to reproduce, etc."
                      className="min-h-[150px]"
                      {...field}
                    />
                  </FormControl>
                  <FormDescription>
                    Please provide as much detail as possible to help us resolve your issue quickly.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            <Card className="border-dashed">
              <CardHeader>
                <CardTitle className="text-lg">Website Support Intake</CardTitle>
                <CardDescription>
                  Use this when the request is for a WordPress site hosted, built, or maintained by Kre8iv.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-5">
                <label className="flex items-start gap-3 rounded-md border p-4">
                  <Checkbox
                    checked={isWebsiteSupport}
                    onCheckedChange={(checked) => setIsWebsiteSupport(checked === true)}
                    className="mt-1"
                  />
                  <span>
                    <span className="block font-medium">This is a website or WordPress support request</span>
                    <span className="block text-sm text-muted-foreground">
                      The system will classify risk, prepare staff routing, and generate an OpenAI Codex-ready prompt
                      for low and medium risk tickets.
                    </span>
                  </span>
                </label>

                {isWebsiteSupport && (
                  <div className="space-y-5">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <FormItem>
                        <FormLabel>Client or Site Name</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.clientName}
                            onChange={(event) => updateWebsiteSupportField("clientName", event.target.value)}
                            placeholder="Client name or site name"
                          />
                        </FormControl>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Production Website URL</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.websiteUrl}
                            onChange={(event) => updateWebsiteSupportField("websiteUrl", event.target.value)}
                            placeholder="https://example.com"
                          />
                        </FormControl>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Affected Page URL</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.affectedPageUrl}
                            onChange={(event) => updateWebsiteSupportField("affectedPageUrl", event.target.value)}
                            placeholder="https://example.com/page"
                          />
                        </FormControl>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Staging URL</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.stagingUrl}
                            onChange={(event) => updateWebsiteSupportField("stagingUrl", event.target.value)}
                            placeholder="Optional staging URL"
                          />
                        </FormControl>
                      </FormItem>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <FormItem>
                        <FormLabel>Requested Change</FormLabel>
                        <FormControl>
                          <Textarea
                            value={websiteSupport.requestedChange}
                            onChange={(event) => updateWebsiteSupportField("requestedChange", event.target.value)}
                            placeholder="What should be changed?"
                            className="min-h-[110px]"
                          />
                        </FormControl>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Problem Description</FormLabel>
                        <FormControl>
                          <Textarea
                            value={websiteSupport.problemDescription}
                            onChange={(event) => updateWebsiteSupportField("problemDescription", event.target.value)}
                            placeholder="What is wrong or missing right now?"
                            className="min-h-[110px]"
                          />
                        </FormControl>
                      </FormItem>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <FormItem>
                        <FormLabel>Platform / Builder</FormLabel>
                        <Select
                          value={websiteSupport.platformBuilder}
                          onValueChange={(value) => updateWebsiteSupportField("platformBuilder", value as WebsiteSupportPlatform)}
                        >
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            {websiteSupportPlatformOptions.map((option) => (
                              <SelectItem key={option} value={option}>
                                {option}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Device Affected</FormLabel>
                        <Select
                          value={websiteSupport.deviceAffected}
                          onValueChange={(value) => updateWebsiteSupportField("deviceAffected", value)}
                        >
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="desktop">Desktop</SelectItem>
                            <SelectItem value="tablet">Tablet</SelectItem>
                            <SelectItem value="mobile">Mobile</SelectItem>
                            <SelectItem value="unknown">Unknown</SelectItem>
                          </SelectContent>
                        </Select>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Browser Affected</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.browserAffected}
                            onChange={(event) => updateWebsiteSupportField("browserAffected", event.target.value)}
                            placeholder="Chrome, Safari, all, unknown"
                          />
                        </FormControl>
                      </FormItem>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <FormItem>
                        <FormLabel>Urgency</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.urgency}
                            onChange={(event) => updateWebsiteSupportField("urgency", event.target.value)}
                            placeholder="No rush, this week, launch blocker, site down"
                          />
                        </FormControl>
                      </FormItem>

                      <FormItem>
                        <FormLabel>Business Impact</FormLabel>
                        <FormControl>
                          <Input
                            value={websiteSupport.businessImpact}
                            onChange={(event) => updateWebsiteSupportField("businessImpact", event.target.value)}
                            placeholder="Cosmetic, lead capture affected, revenue affected"
                          />
                        </FormControl>
                      </FormItem>
                    </div>

                    <FormItem>
                      <FormLabel>Affected Areas</FormLabel>
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 rounded-md border p-4">
                        {websiteSupportAffectedAreaOptions.map((area) => (
                          <label key={area} className="flex items-center gap-2 text-sm capitalize">
                            <Checkbox
                              checked={websiteSupport.affectedAreas.includes(area)}
                              onCheckedChange={(checked) => toggleAffectedArea(area, checked === true)}
                            />
                            {area.replace(/_/g, " ")}
                          </label>
                        ))}
                      </div>
                    </FormItem>

                    <FormItem>
                      <FormLabel>Areas Not to Change</FormLabel>
                      <FormControl>
                        <Textarea
                          value={websiteSupport.areasNotToChange}
                          onChange={(event) => updateWebsiteSupportField("areasNotToChange", event.target.value)}
                          placeholder="Header, footer, checkout, global colors, installed plugins, etc."
                        />
                      </FormControl>
                    </FormItem>
                  </div>
                )}
              </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <FormField
                control={form.control}
                name="category"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Category</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select a category" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="technical">Technical</SelectItem>
                        <SelectItem value="billing">Billing</SelectItem>
                        <SelectItem value="general">General</SelectItem>
                        <SelectItem value="feature_request">Feature Request</SelectItem>
                        <SelectItem value="bug_report">Bug Report</SelectItem>
                        <SelectItem value="security">Security</SelectItem>
                        <SelectItem value="performance">Performance</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="priority"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Priority</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select priority" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="low">Low - General inquiry</SelectItem>
                        <SelectItem value="medium">Medium - Standard issue</SelectItem>
                        <SelectItem value="high">High - Important issue</SelectItem>
                        <SelectItem value="urgent">Urgent - Critical issue</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>
                      Priority determines response time (Urgent: 1h, High: 4h, Medium: 8h, Low: 24h)
                    </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

              <FormItem>
                <FormLabel>Requested Due Date</FormLabel>
                <FormControl>
                  <Input
                    type="date"
                    value={requestedDueDate}
                    onChange={(e) => setRequestedDueDate(e.target.value)}
                  />
                </FormControl>
                <FormDescription>Optional target date for resolution</FormDescription>
              </FormItem>
            </div>

            {staffUsers.length > 0 && (
              <FormField
                control={form.control}
                name="assignedTo"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Assign To (Optional)</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select staff member" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="unassigned">Unassigned</SelectItem>
                        {staffUsers.map((user) => (
                          <SelectItem key={user.id} value={user.id}>
                            {user.name} ({user.email})
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FormDescription>Leave unassigned for automatic assignment based on workload</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

            <FormItem>
              <FormLabel>Attachments</FormLabel>
              <FormControl>
                <div className="space-y-4">
                  <div className="flex items-center gap-4">
                    <Input
                      type="file"
                      accept="image/*"
                      multiple
                      onChange={handleFileUpload}
                      disabled={isUploading}
                      className="cursor-pointer"
                    />
                    {isUploading && <p className="text-sm text-muted-foreground">Uploading...</p>}
                  </div>

                  {uploadedFiles.length > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                      {uploadedFiles.map((file, index) => (
                        <div key={index} className="relative group border rounded-lg overflow-hidden">
                          <img src={file.url} alt={file.name} className="w-full h-32 object-cover" />
                          <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <Button
                              type="button"
                              variant="destructive"
                              size="sm"
                              onClick={() => removeFile(index)}
                              className="gap-2"
                            >
                              <X className="h-4 w-4" />
                              Remove
                            </Button>
                          </div>
                          <div className="p-2 bg-muted">
                            <p className="text-xs truncate" title={file.name}>
                              {file.name}
                            </p>
                            <p className="text-xs text-muted-foreground">{(file.size / 1024).toFixed(1)} KB</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </FormControl>
              <FormDescription>
                Upload images related to your issue (max 5MB per file, image formats only)
              </FormDescription>
            </FormItem>

            <div className="flex gap-4">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? "Creating..." : "Create Ticket"}
              </Button>
              <Button type="button" variant="outline" onClick={() => router.back()} disabled={isSubmitting}>
                Cancel
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}
