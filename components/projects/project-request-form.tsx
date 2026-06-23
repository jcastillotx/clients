"use client";

import { FormEvent, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, Upload } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

interface ClientOption {
  id: string;
  company_name: string;
}

interface ProjectRequestFormProps {
  clients: ClientOption[];
  canSelectClient: boolean;
  defaultClientId?: string;
}

interface CreatedProjectRequest {
  id: string;
  client_id: string;
}

const nameFromFilename = (filename: string) => filename.replace(/\.[^/.]+$/, "");

export function ProjectRequestForm({ clients, canSelectClient, defaultClientId }: ProjectRequestFormProps) {
  const router = useRouter();
  const [title, setTitle] = useState("");
  const [executiveSummary, setExecutiveSummary] = useState("");
  const [description, setDescription] = useState("");
  const [desiredOutcome, setDesiredOutcome] = useState("");
  const [priority, setPriority] = useState<"low" | "medium" | "high">("medium");
  const [requestedStartDate, setRequestedStartDate] = useState("");
  const [requestedLaunchDate, setRequestedLaunchDate] = useState("");
  const [budgetRange, setBudgetRange] = useState("");
  const [clientId, setClientId] = useState(defaultClientId || "");
  const [attachments, setAttachments] = useState<File[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [uploadProgressText, setUploadProgressText] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const selectedClientId = useMemo(() => {
    if (canSelectClient) {
      return clientId;
    }
    return defaultClientId || "";
  }, [canSelectClient, clientId, defaultClientId]);

  const handleFileChange = (fileList: FileList | null) => {
    if (!fileList) {
      return;
    }
    setAttachments(Array.from(fileList));
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);
    setUploadProgressText(null);

    try {
      const requestRow = await fetchApi<CreatedProjectRequest>(
        "/api/projects/requests",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            clientId: selectedClientId || undefined,
            title,
            executiveSummary,
            description: description || null,
            desiredOutcome: desiredOutcome || null,
            priority,
            requestedStartDate: requestedStartDate || null,
            requestedLaunchDate: requestedLaunchDate || null,
            dueDate: requestedLaunchDate || null,
            budgetRange: budgetRange || null,
          }),
        },
        { fallbackMessage: "Failed to create project request" },
      );
      if (!requestRow?.id || !requestRow?.client_id) {
        throw new Error("Project request was created but response payload was invalid");
      }

      if (attachments.length > 0) {
        let uploadedCount = 0;

        for (const file of attachments) {
          uploadedCount += 1;
          setUploadProgressText(`Uploading files (${uploadedCount}/${attachments.length})...`);

          const formData = new FormData();
          formData.append("file", file);
          formData.append("name", nameFromFilename(file.name));
          formData.append("description", "Project request attachment");
          formData.append("clientId", requestRow.client_id);
          formData.append("requestId", requestRow.id);

          await fetchApi(
            "/api/documents/upload",
            { method: "POST", body: formData },
            { fallbackMessage: `Failed to upload ${file.name}` },
          );
        }
      }

      router.push(`/projects/requests/${requestRow.id}`);
      router.refresh();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Failed to submit project request");
    } finally {
      setIsSubmitting(false);
      setUploadProgressText(null);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Project Request</CardTitle>
          <CardDescription>
            Submit your executive summary, timeline, files, and goals. Our team will review and respond with an estimate.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

          {canSelectClient ? (
            <div className="space-y-2">
              <Label htmlFor="client">Client</Label>
              <Select value={clientId} onValueChange={setClientId}>
                <SelectTrigger id="client">
                  <SelectValue placeholder="Select a client" />
                </SelectTrigger>
                <SelectContent>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}

          <div className="space-y-2">
            <Label htmlFor="title">Project Title</Label>
            <Input
              id="title"
              required
              value={title}
              onChange={(event) => setTitle(event.target.value)}
              placeholder="Example: Website redesign and lead generation funnel"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="executive-summary">Executive Summary</Label>
            <Textarea
              id="executive-summary"
              required
              value={executiveSummary}
              onChange={(event) => setExecutiveSummary(event.target.value)}
              rows={5}
              placeholder="Summarize what success looks like, who this project serves, and why it matters."
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Detailed Scope</Label>
            <Textarea
              id="description"
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              rows={6}
              placeholder="Include deliverables, platforms, integrations, and required capabilities."
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="desired-outcome">Desired Outcome / KPIs</Label>
            <Textarea
              id="desired-outcome"
              value={desiredOutcome}
              onChange={(event) => setDesiredOutcome(event.target.value)}
              rows={4}
              placeholder="Example: Increase qualified leads by 25% in 90 days."
            />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="priority">Priority</Label>
              <Select value={priority} onValueChange={(value: "low" | "medium" | "high") => setPriority(value)}>
                <SelectTrigger id="priority">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="budget-range">Budget Range (optional)</Label>
              <Input
                id="budget-range"
                value={budgetRange}
                onChange={(event) => setBudgetRange(event.target.value)}
                placeholder="Example: $8,000 - $12,000"
              />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="requested-start">Requested Start Date</Label>
              <Input
                id="requested-start"
                type="date"
                value={requestedStartDate}
                onChange={(event) => setRequestedStartDate(event.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="requested-launch">Requested Due Date / Launch Date</Label>
              <Input
                id="requested-launch"
                type="date"
                value={requestedLaunchDate}
                onChange={(event) => setRequestedLaunchDate(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="attachments">Project Files</Label>
            <Input
              id="attachments"
              type="file"
              multiple
              onChange={(event) => handleFileChange(event.target.files)}
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.webp,.zip"
            />
            <p className="text-xs text-muted-foreground">
              Upload briefs, references, wireframes, requirements, or any files needed for estimate and review.
            </p>
            {attachments.length > 0 ? (
              <ul className="rounded-md border bg-muted/30 p-3 text-sm">
                {attachments.map((file) => (
                  <li key={file.name} className="flex items-center gap-2 py-0.5">
                    <Upload className="h-3.5 w-3.5 text-muted-foreground" />
                    <span>{file.name}</span>
                  </li>
                ))}
              </ul>
            ) : null}
          </div>
        </CardContent>
      </Card>

      <div className="flex items-center justify-end gap-3">
        {uploadProgressText ? <span className="text-sm text-muted-foreground">{uploadProgressText}</span> : null}
        <Button type="button" variant="outline" onClick={() => router.back()} disabled={isSubmitting}>
          Cancel
        </Button>
        <Button type="submit" disabled={isSubmitting || !title || !executiveSummary || (canSelectClient && !selectedClientId)}>
          {isSubmitting ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Submitting...
            </>
          ) : (
            "Submit Project Request"
          )}
        </Button>
      </div>
    </form>
  );
}
