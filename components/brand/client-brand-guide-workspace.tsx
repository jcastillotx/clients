"use client";

import { useState } from "react";
import { Eye, FileUp, Loader2, Pencil, Plus, Save, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { ClientBrandGuidePreview } from "@/components/brand/client-brand-guide-preview";
import type { ClientBrandDocument } from "@/components/brand/client-brand-guide-preview";
import { UploadDialog } from "@/components/documents/upload-dialog";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { fetchApi } from "@/lib/api/client";
import {
  clientBrandGuideContentSchema,
  type ClientBrandGuideContent,
} from "@/lib/brand/client-brand-guide";

interface ClientBrandGuideWorkspaceProps {
  client: {
    id: string;
    company_name: string;
  };
  initialGuideId: string | null;
  initialContent: ClientBrandGuideContent;
  initialStatus: "draft" | "published";
  initialDocuments: ClientBrandDocument[];
}

interface SavedGuide {
  id: string;
  status: "draft" | "published";
}

export function ClientBrandGuideWorkspace({
  client,
  initialGuideId,
  initialContent,
  initialStatus,
  initialDocuments,
}: ClientBrandGuideWorkspaceProps) {
  const [guideId, setGuideId] = useState(initialGuideId);
  const [content, setContent] = useState(initialContent);
  const [status, setStatus] = useState(initialStatus);
  const [documents, setDocuments] = useState(initialDocuments);
  const [isEditing, setIsEditing] = useState(!initialGuideId);
  const [isSaving, setIsSaving] = useState(false);
  const [uploadOpen, setUploadOpen] = useState(false);

  const updateContent = <Key extends keyof ClientBrandGuideContent>(
    key: Key,
    value: ClientBrandGuideContent[Key],
  ) => {
    setContent((current) => ({ ...current, [key]: value }));
  };

  const addColor = () => {
    updateContent("colors", [
      ...content.colors,
      {
        id: crypto.randomUUID(),
        name: "New color",
        hex: "#111827",
        usage: "",
      },
    ]);
  };

  const updateColor = (
    colorId: string,
    field: "name" | "hex" | "usage",
    value: string,
  ) => {
    updateContent(
      "colors",
      content.colors.map((color) =>
        color.id === colorId ? { ...color, [field]: value } : color,
      ),
    );
  };

  const removeColor = (colorId: string) => {
    updateContent(
      "colors",
      content.colors.filter((color) => color.id !== colorId),
    );
  };

  const handleSave = async () => {
    const parsed = clientBrandGuideContentSchema.safeParse(content);
    if (!parsed.success) {
      toast.error("Check the brand guide fields", {
        description: parsed.error.errors[0]?.message,
      });
      return;
    }

    setIsSaving(true);
    try {
      const guide = await fetchApi<SavedGuide>(
        `/api/clients/${client.id}/brand-guide`,
        {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ status, content: parsed.data }),
        },
        { fallbackMessage: "Failed to save brand guide" },
      );

      setGuideId(guide.id);
      setContent(parsed.data);
      setStatus(guide.status);
      setIsEditing(false);
      toast.success(
        status === "published" ? "Brand guide published" : "Brand guide saved",
      );
    } catch (error) {
      toast.error("Could not save the brand guide", {
        description: error instanceof Error ? error.message : "Unknown error",
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleDownload = async (documentId: string) => {
    try {
      const result = await fetchApi<{ url: string; fileName: string }>(
        `/api/documents/${documentId}/download`,
        undefined,
        { fallbackMessage: "Failed to prepare download" },
      );
      const link = document.createElement("a");
      link.href = result.url;
      link.download = result.fileName;
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      toast.error("Could not download the file", {
        description: error instanceof Error ? error.message : "Unknown error",
      });
    }
  };

  const handleUploadSuccess = (document: Record<string, unknown>) => {
    const uploaded = document as unknown as ClientBrandDocument;
    setDocuments((current) => [uploaded, ...current]);
    setUploadOpen(false);
    toast.success("Brand asset uploaded");
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-3xl font-bold tracking-tight">Brand guide</h1>
            <Badge
              variant={status === "published" ? "default" : "secondary"}
              className="capitalize"
            >
              {status}
            </Badge>
          </div>
          <p className="mt-1 text-muted-foreground">
            Keep {client.company_name}&apos;s visual identity, messaging, and
            approved assets in one place.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => setUploadOpen(true)}
          >
            <FileUp className="mr-2 h-4 w-4" aria-hidden />
            Upload asset
          </Button>
          {isEditing ? (
            <Button
              type="button"
              onClick={() => void handleSave()}
              disabled={isSaving}
            >
              {isSaving ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
              ) : (
                <Save className="mr-2 h-4 w-4" aria-hidden />
              )}
              Save guide
            </Button>
          ) : (
            <Button type="button" onClick={() => setIsEditing(true)}>
              <Pencil className="mr-2 h-4 w-4" aria-hidden />
              Edit guide
            </Button>
          )}
        </div>
      </div>

      {!guideId ? (
        <Alert>
          <Eye className="h-4 w-4" aria-hidden />
          <AlertDescription>
            This client does not have a saved brand guide yet. Add what you know
            now; incomplete sections will remain clearly marked in the preview.
          </AlertDescription>
        </Alert>
      ) : null}

      <div
        className={
          isEditing
            ? "grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]"
            : "mx-auto max-w-5xl"
        }
      >
        {isEditing ? (
          <Card className="h-fit xl:sticky xl:top-6">
            <CardHeader>
              <div className="flex items-center justify-between gap-3">
                <CardTitle className="text-lg">Guide details</CardTitle>
                <Select
                  value={status}
                  onValueChange={(value) =>
                    setStatus(value as "draft" | "published")
                  }
                >
                  <SelectTrigger className="w-32" aria-label="Guide status">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Draft</SelectItem>
                    <SelectItem value="published">Published</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardHeader>
            <CardContent>
              <Tabs defaultValue="foundation" className="space-y-5">
                <TabsList className="grid h-auto w-full grid-cols-2">
                  <TabsTrigger value="foundation">Foundation</TabsTrigger>
                  <TabsTrigger value="visual">Visual</TabsTrigger>
                  <TabsTrigger value="voice">Voice</TabsTrigger>
                  <TabsTrigger value="applications">Applications</TabsTrigger>
                </TabsList>

                <TabsContent value="foundation" className="space-y-4">
                  <Field label="Guide title">
                    <Input
                      value={content.title}
                      onChange={(event) =>
                        updateContent("title", event.target.value)
                      }
                    />
                  </Field>
                  <Field label="Introduction">
                    <Textarea
                      rows={3}
                      value={content.summary}
                      onChange={(event) =>
                        updateContent("summary", event.target.value)
                      }
                      placeholder="A short introduction to the brand system"
                    />
                  </Field>
                  <Field label="Tagline">
                    <Input
                      value={content.tagline}
                      onChange={(event) =>
                        updateContent("tagline", event.target.value)
                      }
                      placeholder="A memorable brand line"
                    />
                  </Field>
                  <Field label="Positioning">
                    <Textarea
                      rows={4}
                      value={content.positioning}
                      onChange={(event) =>
                        updateContent("positioning", event.target.value)
                      }
                      placeholder="What makes this brand distinct?"
                    />
                  </Field>
                  <Field label="Mission">
                    <Textarea
                      rows={3}
                      value={content.mission}
                      onChange={(event) =>
                        updateContent("mission", event.target.value)
                      }
                    />
                  </Field>
                  <Field label="Audience">
                    <Textarea
                      rows={3}
                      value={content.audience}
                      onChange={(event) =>
                        updateContent("audience", event.target.value)
                      }
                    />
                  </Field>
                  <Field label="Personality">
                    <Textarea
                      rows={3}
                      value={content.personality}
                      onChange={(event) =>
                        updateContent("personality", event.target.value)
                      }
                      placeholder="Confident, warm, precise…"
                    />
                  </Field>
                </TabsContent>

                <TabsContent value="visual" className="space-y-5">
                  <Field
                    label="Primary logo URL"
                    hint="Use an approved hosted logo. Upload source files under Brand assets below."
                  >
                    <Input
                      type="url"
                      value={content.logoUrl}
                      onChange={(event) =>
                        updateContent("logoUrl", event.target.value)
                      }
                      placeholder="https://example.com/logo.svg"
                    />
                  </Field>
                  <Field label="Logo usage">
                    <Textarea
                      rows={4}
                      value={content.logoNotes}
                      onChange={(event) =>
                        updateContent("logoNotes", event.target.value)
                      }
                      placeholder="Clear space, minimum size, backgrounds, and misuse"
                    />
                  </Field>

                  <div className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                      <Label>Color palette</Label>
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={addColor}
                        disabled={content.colors.length >= 12}
                      >
                        <Plus className="mr-2 h-4 w-4" aria-hidden />
                        Add color
                      </Button>
                    </div>
                    {content.colors.length ? (
                      <div className="space-y-3">
                        {content.colors.map((color) => (
                          <div
                            key={color.id}
                            className="space-y-3 rounded-lg border p-3"
                          >
                            <div className="flex items-center gap-2">
                              <Input
                                type="color"
                                value={color.hex}
                                onChange={(event) =>
                                  updateColor(
                                    color.id,
                                    "hex",
                                    event.target.value,
                                  )
                                }
                                className="h-10 w-14 p-1"
                                aria-label={`${color.name} color`}
                              />
                              <Input
                                value={color.name}
                                onChange={(event) =>
                                  updateColor(
                                    color.id,
                                    "name",
                                    event.target.value,
                                  )
                                }
                                placeholder="Color name"
                              />
                              <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                onClick={() => removeColor(color.id)}
                                aria-label={`Remove ${color.name}`}
                              >
                                <Trash2 className="h-4 w-4" aria-hidden />
                              </Button>
                            </div>
                            <div className="grid gap-2 sm:grid-cols-[120px_1fr]">
                              <Input
                                value={color.hex}
                                onChange={(event) =>
                                  updateColor(
                                    color.id,
                                    "hex",
                                    event.target.value,
                                  )
                                }
                                aria-label={`${color.name} hex value`}
                              />
                              <Input
                                value={color.usage}
                                onChange={(event) =>
                                  updateColor(
                                    color.id,
                                    "usage",
                                    event.target.value,
                                  )
                                }
                                placeholder="How this color should be used"
                              />
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="rounded-lg border border-dashed p-5 text-center text-sm text-muted-foreground">
                        No colors added yet.
                      </p>
                    )}
                  </div>

                  <Field label="Heading font">
                    <Input
                      value={content.headingFont}
                      onChange={(event) =>
                        updateContent("headingFont", event.target.value)
                      }
                      placeholder="e.g. Montserrat"
                    />
                  </Field>
                  <Field label="Body font">
                    <Input
                      value={content.bodyFont}
                      onChange={(event) =>
                        updateContent("bodyFont", event.target.value)
                      }
                      placeholder="e.g. Inter"
                    />
                  </Field>
                  <Field label="Typography guidance">
                    <Textarea
                      rows={4}
                      value={content.typographyNotes}
                      onChange={(event) =>
                        updateContent("typographyNotes", event.target.value)
                      }
                    />
                  </Field>
                </TabsContent>

                <TabsContent value="voice" className="space-y-4">
                  <Field label="Tone">
                    <Textarea
                      rows={5}
                      value={content.voiceTone}
                      onChange={(event) =>
                        updateContent("voiceTone", event.target.value)
                      }
                      placeholder="Describe how the brand should sound"
                    />
                  </Field>
                  <Field label="Do">
                    <Textarea
                      rows={5}
                      value={content.voiceDo}
                      onChange={(event) =>
                        updateContent("voiceDo", event.target.value)
                      }
                      placeholder="Short sentences, clear language…"
                    />
                  </Field>
                  <Field label="Avoid">
                    <Textarea
                      rows={5}
                      value={content.voiceAvoid}
                      onChange={(event) =>
                        updateContent("voiceAvoid", event.target.value)
                      }
                      placeholder="Jargon, hype, vague claims…"
                    />
                  </Field>
                </TabsContent>

                <TabsContent value="applications" className="space-y-4">
                  <Field label="Application guidance">
                    <Textarea
                      rows={7}
                      value={content.applicationNotes}
                      onChange={(event) =>
                        updateContent("applicationNotes", event.target.value)
                      }
                      placeholder="How the system appears in web, print, social, presentations, and other client touchpoints"
                    />
                  </Field>
                  <div className="rounded-lg border border-dashed p-5 text-center">
                    <p className="text-sm font-medium">Brand assets</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Logo packages, templates, imagery, and source files are
                      stored privately with this client.
                    </p>
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      className="mt-4"
                      onClick={() => setUploadOpen(true)}
                    >
                      <FileUp className="mr-2 h-4 w-4" aria-hidden />
                      Upload asset
                    </Button>
                  </div>
                </TabsContent>
              </Tabs>
            </CardContent>
          </Card>
        ) : null}

        <ClientBrandGuidePreview
          companyName={client.company_name}
          content={content}
          status={status}
          documents={documents}
          onDownload={(documentId) => void handleDownload(documentId)}
        />
      </div>

      <UploadDialog
        open={uploadOpen}
        onOpenChange={setUploadOpen}
        clients={[client]}
        defaultClientId={client.id}
        defaultTags={["brand"]}
        hideClientField
        onSuccess={handleUploadSuccess}
      />
    </div>
  );
}

function Field({
  label,
  hint,
  children,
}: {
  label: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      {children}
      {hint ? (
        <p className="text-xs leading-5 text-muted-foreground">{hint}</p>
      ) : null}
    </div>
  );
}
