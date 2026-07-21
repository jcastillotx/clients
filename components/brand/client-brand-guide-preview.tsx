import {
  Building2,
  Download,
  FileText,
  ImageIcon,
  MessageSquareQuote,
  Palette,
  Type,
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import type { ClientBrandGuideContent } from "@/lib/brand/client-brand-guide";
import { formatFileSize } from "@/lib/storage/utils";

export interface ClientBrandDocument {
  id: string;
  name: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  tags: string[] | null;
  created_at: string;
}

interface ClientBrandGuidePreviewProps {
  companyName: string;
  content: ClientBrandGuideContent;
  status: "draft" | "published";
  documents: ClientBrandDocument[];
  onDownload: (documentId: string) => void;
}

function EmptyCopy({ children }: { children: string }) {
  return <span className="text-muted-foreground/70">{children}</span>;
}

export function ClientBrandGuidePreview({
  companyName,
  content,
  status,
  documents,
  onDownload,
}: ClientBrandGuidePreviewProps) {
  return (
    <div className="overflow-hidden rounded-xl border bg-white text-slate-950 shadow-sm">
      <header className="border-b px-6 py-8 sm:px-10 sm:py-12">
        <div className="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
          <div className="max-w-2xl space-y-4">
            <div className="flex items-center gap-3">
              {content.logoUrl ? (
                <div className="flex h-14 w-14 items-center justify-center rounded-lg border bg-white p-2">
                  {/* The URL is supplied by authorized staff and may point to any image host. */}
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={content.logoUrl}
                    alt={`${companyName} logo`}
                    className="max-h-full max-w-full object-contain"
                  />
                </div>
              ) : (
                <div className="flex h-14 w-14 items-center justify-center rounded-lg border bg-slate-50">
                  <Building2 className="h-7 w-7 text-slate-500" aria-hidden />
                </div>
              )}
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                  {companyName}
                </p>
                <Badge
                  variant={status === "published" ? "default" : "secondary"}
                  className="mt-2 capitalize"
                >
                  {status}
                </Badge>
              </div>
            </div>
            <h1 className="text-3xl font-semibold tracking-tight sm:text-5xl">
              {content.title}
            </h1>
            <p className="max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
              {content.summary || (
                <EmptyCopy>Add a short introduction to the brand.</EmptyCopy>
              )}
            </p>
          </div>
          {content.tagline ? (
            <blockquote className="max-w-xs border-l-2 border-slate-900 pl-4 text-lg font-medium leading-7">
              “{content.tagline}”
            </blockquote>
          ) : null}
        </div>
      </header>

      <main className="space-y-16 px-6 py-10 sm:px-10 sm:py-14">
        <section className="space-y-6">
          <SectionLabel number="01" label="Brand foundation" />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            What the brand stands for.
          </h2>
          <div className="divide-y rounded-lg border">
            <FoundationRow label="Positioning" value={content.positioning} />
            <FoundationRow label="Mission" value={content.mission} />
            <FoundationRow label="Audience" value={content.audience} />
            <FoundationRow label="Personality" value={content.personality} />
          </div>
        </section>

        <section className="space-y-6">
          <SectionLabel number="02" label="Logo" icon={ImageIcon} />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            The primary mark.
          </h2>
          <div className="flex min-h-64 items-center justify-center rounded-xl border bg-slate-50 p-10">
            {content.logoUrl ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={content.logoUrl}
                alt={`${companyName} primary logo`}
                className="max-h-36 max-w-full object-contain"
              />
            ) : (
              <div className="text-center text-slate-500">
                <ImageIcon className="mx-auto mb-3 h-9 w-9" aria-hidden />
                <p className="font-medium">Primary logo not added yet</p>
              </div>
            )}
          </div>
          <p className="text-sm leading-6 text-slate-600">
            {content.logoNotes || (
              <EmptyCopy>
                Add clear-space, minimum-size, and misuse guidance.
              </EmptyCopy>
            )}
          </p>
        </section>

        <section className="space-y-6">
          <SectionLabel number="03" label="Color" icon={Palette} />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            The color system.
          </h2>
          {content.colors.length ? (
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {content.colors.map((color) => (
                <div
                  key={color.id}
                  className="overflow-hidden rounded-lg border"
                >
                  <div
                    className="h-28 border-b"
                    style={{ backgroundColor: color.hex }}
                    aria-label={`${color.name} color swatch ${color.hex}`}
                  />
                  <div className="space-y-1 p-4">
                    <div className="flex items-center justify-between gap-3">
                      <p className="font-medium">{color.name}</p>
                      <code className="text-xs text-slate-500">
                        {color.hex}
                      </code>
                    </div>
                    {color.usage ? (
                      <p className="text-xs leading-5 text-slate-500">
                        {color.usage}
                      </p>
                    ) : null}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <EmptyPanel
              icon={Palette}
              text="Add the client’s primary, secondary, and accent colors."
            />
          )}
        </section>

        <section className="space-y-6">
          <SectionLabel number="04" label="Typography" icon={Type} />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            Type with a clear job.
          </h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <TypeCard
              label="Headings"
              font={content.headingFont}
              sample="Make the message memorable."
            />
            <TypeCard
              label="Body"
              font={content.bodyFont}
              sample="Clear, useful language makes the brand easier to trust."
            />
          </div>
          {content.typographyNotes ? (
            <p className="text-sm leading-6 text-slate-600">
              {content.typographyNotes}
            </p>
          ) : null}
        </section>

        <section className="space-y-6">
          <SectionLabel number="05" label="Voice" icon={MessageSquareQuote} />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            How the brand sounds.
          </h2>
          <div className="grid gap-4 lg:grid-cols-3">
            <VoiceCard label="Tone" value={content.voiceTone} />
            <VoiceCard label="Do" value={content.voiceDo} />
            <VoiceCard label="Avoid" value={content.voiceAvoid} />
          </div>
        </section>

        <section className="space-y-6">
          <SectionLabel
            number="06"
            label="Assets & applications"
            icon={FileText}
          />
          <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">
            Put the brand to work.
          </h2>
          {content.applicationNotes ? (
            <p className="max-w-3xl text-sm leading-6 text-slate-600">
              {content.applicationNotes}
            </p>
          ) : null}
          {documents.length ? (
            <div className="grid gap-3 sm:grid-cols-2">
              {documents.map((document) => (
                <Card key={document.id} className="shadow-none">
                  <CardContent className="flex items-center gap-3 p-4">
                    <div className="rounded-md bg-slate-100 p-2.5">
                      <FileText
                        className="h-5 w-5 text-slate-600"
                        aria-hidden
                      />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium">
                        {document.name}
                      </p>
                      <p className="text-xs text-slate-500">
                        {formatFileSize(document.file_size)}
                      </p>
                    </div>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      onClick={() => onDownload(document.id)}
                      aria-label={`Download ${document.name}`}
                    >
                      <Download className="h-4 w-4" aria-hidden />
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <EmptyPanel
              icon={FileText}
              text="Upload logo packages, templates, and other approved brand assets."
            />
          )}
        </section>
      </main>

      <footer className="px-6 pb-10 sm:px-10">
        <Separator className="mb-6" />
        <p className="text-xs text-slate-500">
          {companyName} · Brand guide · Managed in the client portal
        </p>
      </footer>
    </div>
  );
}

function SectionLabel({
  number,
  label,
  icon: Icon,
}: {
  number: string;
  label: string;
  icon?: typeof Palette;
}) {
  return (
    <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
      {Icon ? <Icon className="h-3.5 w-3.5" aria-hidden /> : null}
      <span>{number}</span>
      <span>—</span>
      <span>{label}</span>
    </div>
  );
}

function FoundationRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="grid gap-2 p-4 sm:grid-cols-[140px_1fr] sm:gap-6 sm:p-5">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">
        {label}
      </p>
      <p className="text-sm leading-6 text-slate-700">
        {value || <EmptyCopy>Not added yet.</EmptyCopy>}
      </p>
    </div>
  );
}

function TypeCard({
  label,
  font,
  sample,
}: {
  label: string;
  font: string;
  sample: string;
}) {
  return (
    <div className="rounded-lg border p-5">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">
        {label}
      </p>
      <p className="mt-2 text-sm font-medium">{font || "Font not selected"}</p>
      <p
        className="mt-8 text-2xl leading-tight"
        style={font ? { fontFamily: font } : undefined}
      >
        {sample}
      </p>
    </div>
  );
}

function VoiceCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border p-5">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">
        {label}
      </p>
      <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">
        {value || <EmptyCopy>Not added yet.</EmptyCopy>}
      </p>
    </div>
  );
}

function EmptyPanel({
  icon: Icon,
  text,
}: {
  icon: typeof Palette;
  text: string;
}) {
  return (
    <div className="rounded-lg border border-dashed bg-slate-50 p-8 text-center text-slate-500">
      <Icon className="mx-auto mb-3 h-7 w-7" aria-hidden />
      <p className="text-sm">{text}</p>
    </div>
  );
}
