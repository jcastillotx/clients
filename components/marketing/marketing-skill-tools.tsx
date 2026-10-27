"use client";

import { useCallback, useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { SkillArtifact } from "@/lib/ai/marketing-skills/execution";
import marketingLicense from "@/lib/ai/marketing-skills/license.json";

type Tool = { id: string; name: string; description: string; category: string };
type Run = {
  id: string;
  skillId: string;
  status: string;
  createdAt: string;
  output: { result?: SkillArtifact } | null;
  metadata: { provider?: string } | null;
  error: string | null;
};
type Library = {
  clientId: string;
  tools: Tool[];
  providerReady: boolean;
  providerName: "openai" | "openrouter" | "local_simulator" | null;
  simulated: boolean;
  history: Run[];
};
type Envelope<T> = { data?: T; error?: { message?: string }; message?: string };

export function MarketingSkillTools({
  clients,
  isStaff,
}: {
  clients: Array<{ id: string; companyName: string }>;
  isStaff: boolean;
}) {
  const [clientId, setClientId] = useState(clients[0]?.id ?? "");
  const [library, setLibrary] = useState<Library | null>(null);
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("All");
  const [selected, setSelected] = useState<Tool | null>(null);
  const [methodology, setMethodology] = useState("");
  const [brief, setBrief] = useState("");
  const [evidence, setEvidence] = useState("");
  const [artifact, setArtifact] = useState<SkillArtifact | null>(null);
  const [artifactSimulated, setArtifactSimulated] = useState(false);
  const [running, setRunning] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const refresh = useCallback(async () => {
    if (!clientId) return;
    setLoading(true);
    setError("");
    try {
      const response = await fetch(
        `/api/marketing/tools?clientId=${encodeURIComponent(clientId)}`,
      );
      const body = (await response.json()) as Envelope<Library>;
      if (!response.ok || !body.data)
        throw new Error(body.error?.message || "Unable to load tools.");
      setLibrary(body.data);
    } catch (e) {
      setLibrary(null);
      setError(e instanceof Error ? e.message : "Unable to load tools.");
    } finally {
      setLoading(false);
    }
  }, [clientId]);
  useEffect(() => {
    setSelected(null);
    setArtifact(null);
    void refresh();
  }, [refresh]);
  useEffect(() => {
    if (!selected) return;
    let active = true;
    setMethodology("");
    fetch(
      `/api/marketing/tools?clientId=${encodeURIComponent(clientId)}&skillId=${encodeURIComponent(selected.id)}`,
    )
      .then(async (r) => {
        const body = (await r.json()) as Envelope<{ methodology: string }>;
        if (!r.ok)
          throw new Error(body.error?.message || "Unable to load methodology.");
        if (active) setMethodology(body.data?.methodology ?? "");
      })
      .catch((e: unknown) => {
        if (active)
          setError(
            e instanceof Error ? e.message : "Unable to load methodology.",
          );
      });
    return () => {
      active = false;
    };
  }, [selected, clientId]);
  async function run() {
    if (!selected || running) return;
    setRunning(true);
    setError("");
    setArtifact(null);
    try {
      const response = await fetch("/api/marketing/tools", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          skillId: selected.id,
          brief,
          evidence,
        }),
      });
      const body = (await response.json()) as Envelope<{
        artifact: SkillArtifact;
        simulated: boolean;
      }>;
      if (!response.ok || !body.data)
        throw new Error(body.error?.message || "Unable to complete draft.");
      setArtifact(body.data.artifact);
      setArtifactSimulated(body.data.simulated);
      await refresh();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to complete draft.");
    } finally {
      setRunning(false);
    }
  }
  function download() {
    if (!artifact) return;
    const text = [
      artifact.title,
      artifact.analysis,
      "Recommendations",
      ...artifact.recommendations,
      "Missing data",
      ...artifact.missingData,
      "Sources",
      ...artifact.sources,
    ].join("\n\n");
    const url = URL.createObjectURL(
      new Blob([text], { type: "text/plain;charset=utf-8" }),
    );
    const link = document.createElement("a");
    link.href = url;
    link.download = `${selected?.id ?? "marketing"}-draft.txt`;
    link.click();
    URL.revokeObjectURL(url);
  }
  const tools = library?.tools ?? [];
  const visible = tools.filter(
    (t) =>
      (category === "All" || t.category === category) &&
      `${t.name} ${t.description}`.toLowerCase().includes(search.toLowerCase()),
  );
  return (
    <div className="space-y-6 p-6">
      <header className="space-y-2">
        <h1 className="text-3xl font-bold">Marketing Tools</h1>
        <p className="text-muted-foreground">
          48 tools for strategy, research, content, paid media, conversion,
          analytics and reporting.
        </p>
        <p className="text-sm text-muted-foreground">
          Use your brief and supplied data to create reviewable drafts. Tools do
          not connect accounts, crawl websites, publish content, send email or
          spend money.
        </p>
      </header>
      {isStaff && (
        <div>
          <Label htmlFor="skill-client">Client account</Label>
          <select
            id="skill-client"
            className="ml-3 rounded border bg-background p-2"
            value={clientId}
            disabled={running}
            onChange={(e) => {
              setLibrary(null);
              setClientId(e.target.value);
            }}
          >
            {clients.map((c) => (
              <option key={c.id} value={c.id}>
                {c.companyName}
              </option>
            ))}
          </select>
        </div>
      )}
      {error && (
        <p
          role="alert"
          className="rounded border border-destructive p-4 text-destructive"
        >
          {error}
        </p>
      )}
      {loading && !library && <p role="status">Loading marketing tools…</p>}
      {library && (
        <>
          <p role="status" className="rounded border p-3 text-sm">
            {library.simulated
              ? "Isolated simulator: new drafts use test fixtures, not real AI analysis."
              : library.providerReady
                ? "AI provider configured. Outputs require review and evidence checks."
                : "AI drafting needs an account provider. You can still open every tool and read its methodology."}
          </p>
          <div className="flex flex-wrap gap-4">
            <div className="min-w-60 flex-1">
              <Label htmlFor="skill-search">Search tools</Label>
              <Input
                id="skill-search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="SEO, Google Ads, content…"
              />
            </div>
            <div>
              <Label htmlFor="skill-category">Category</Label>
              <select
                id="skill-category"
                className="block rounded border bg-background p-2"
                value={category}
                onChange={(e) => setCategory(e.target.value)}
              >
                {["All", ...new Set(tools.map((t) => t.category))].map((c) => (
                  <option key={c}>{c}</option>
                ))}
              </select>
            </div>
          </div>
          <p className="text-sm">
            {visible.length} of {tools.length} tools
          </p>
          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {visible.map((tool) => (
              <Card key={tool.id}>
                <CardHeader>
                  <CardDescription>{tool.category}</CardDescription>
                  <CardTitle className="text-lg">{tool.name}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="mb-4 text-sm text-muted-foreground">
                    {tool.description}
                  </p>
                  <Button
                    variant={selected?.id === tool.id ? "default" : "outline"}
                    disabled={running}
                    onClick={() => {
                      setSelected(tool);
                      setArtifact(null);
                      setError("");
                    }}
                  >
                    {selected?.id === tool.id
                      ? "Selected"
                      : `Open ${tool.name}`}
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
          {selected && (
            <Card>
              <CardHeader>
                <CardTitle>{selected.name}</CardTitle>
                <CardDescription>
                  Provide the task, audience, constraints, and the data you want
                  analyzed. Do not include passwords, API keys, payment details
                  or sensitive personal information.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  {library.simulated
                    ? "This isolated test sends synthetic data only to the local simulator."
                    : `Creating a draft sends your brief, supporting data and this account's brand context to ${library.providerName === "openrouter" ? "OpenRouter and its model provider" : library.providerName === "openai" ? "OpenAI" : "your configured AI provider"}.`}
                </p>
                <details>
                  <summary className="cursor-pointer font-medium">
                    View methodology
                  </summary>
                  <pre className="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded bg-muted p-4 text-xs">
                    {methodology || "Loading methodology…"}
                  </pre>
                </details>
                <div>
                  <Label htmlFor="skill-brief">Your brief</Label>
                  <Textarea
                    id="skill-brief"
                    value={brief}
                    maxLength={8000}
                    disabled={running}
                    onChange={(e) => setBrief(e.target.value)}
                    placeholder="Describe your objective and audience (at least 20 characters)"
                  />
                </div>
                <div>
                  <Label htmlFor="skill-evidence">
                    Supporting data and source references
                  </Label>
                  <Textarea
                    id="skill-evidence"
                    value={evidence}
                    maxLength={40000}
                    disabled={running}
                    onChange={(e) => setEvidence(e.target.value)}
                    placeholder="Paste relevant exports, page text, metrics and source dates. Live account access is not automatic."
                  />
                </div>
                <Button
                  disabled={
                    running ||
                    !library.providerReady ||
                    brief.trim().length < 20
                  }
                  onClick={() => void run()}
                >
                  {running ? "Creating draft…" : "Create draft"}
                </Button>
              </CardContent>
            </Card>
          )}
          {artifact && (
            <Card>
              <CardHeader>
                <CardTitle>{artifact.title}</CardTitle>
                <CardDescription>
                  {artifactSimulated
                    ? "SIMULATED TEST OUTPUT"
                    : "Draft — review before use"}
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <pre className="whitespace-pre-wrap font-sans text-sm">
                  {artifact.analysis}
                </pre>
                <h3 className="font-semibold">Recommendations</h3>
                <ul className="list-disc space-y-2 pl-5">
                  {artifact.recommendations.map((r, i) => (
                    <li key={i}>{r}</li>
                  ))}
                </ul>
                <h3 className="font-semibold">Missing data</h3>
                {artifact.missingData.length ? (
                  <ul className="list-disc pl-5">
                    {artifact.missingData.map((r, i) => (
                      <li key={i}>{r}</li>
                    ))}
                  </ul>
                ) : (
                  <p className="text-sm">
                    No missing data identified by the model; verify this during
                    review.
                  </p>
                )}
                <h3 className="font-semibold">
                  Source references supplied by the model
                </h3>
                <pre className="whitespace-pre-wrap text-sm">
                  {artifact.sources.join("\n") ||
                    "No source references provided."}
                </pre>
                <Button variant="outline" onClick={download}>
                  Download draft
                </Button>
              </CardContent>
            </Card>
          )}
          <Card>
            <CardHeader>
              <CardTitle>Saved drafts and runs</CardTitle>
            </CardHeader>
            <CardContent>
              {!library.history.length ? (
                <p className="text-sm text-muted-foreground">
                  No runs yet for this client account.
                </p>
              ) : (
                <ul className="space-y-3">
                  {library.history.map((r) => (
                    <li
                      key={r.id}
                      className="flex flex-wrap items-center justify-between gap-3 border-b pb-3"
                    >
                      <span>
                        {tools.find((t) => t.id === r.skillId)?.name ??
                          r.skillId}{" "}
                        · {r.status} · {new Date(r.createdAt).toLocaleString()}
                      </span>
                      {r.output?.result && (
                        <Button
                          variant="outline"
                          disabled={running}
                          onClick={() => {
                            setSelected(
                              tools.find((tool) => tool.id === r.skillId) ??
                                null,
                            );
                            setArtifact(r.output!.result!);
                            setArtifactSimulated(
                              r.metadata?.provider === "local_simulator",
                            );
                          }}
                        >
                          View saved draft
                        </Button>
                      )}
                      {r.error && (
                        <p className="text-sm text-destructive">{r.error}</p>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>
        </>
      )}
      <footer className="text-xs text-muted-foreground">
        Marketing methodology adapted from claude-marketing by Rebecca Rae
        Barton, MIT License. Repository administration utilities are excluded.
        <details className="mt-2">
          <summary className="cursor-pointer">Source license</summary>
          <pre className="mt-2 whitespace-pre-wrap">{marketingLicense}</pre>
        </details>
      </footer>
    </div>
  );
}
