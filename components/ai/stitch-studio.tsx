"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { ExternalLink, Loader2, Monitor, Plus, RefreshCw, Smartphone, Sparkles, Tablet } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { fetchApi } from "@/lib/api/client";

type StitchProject = {
  id: string;
  projectId: string;
  title?: string;
};

type StitchScreen = {
  id: string;
  screenId: string;
  projectId: string;
  htmlUrl: string | null;
  imageUrl: string | null;
};

type DeviceType = "MOBILE" | "DESKTOP" | "TABLET" | "AGNOSTIC";

export function StitchStudio() {
  const [configured, setConfigured] = useState<boolean | null>(null);
  const [projects, setProjects] = useState<StitchProject[]>([]);
  const [screens, setScreens] = useState<StitchScreen[]>([]);
  const [selectedProjectId, setSelectedProjectId] = useState<string>("");
  const [selectedScreen, setSelectedScreen] = useState<StitchScreen | null>(null);
  const [newProjectTitle, setNewProjectTitle] = useState("");
  const [prompt, setPrompt] = useState("");
  const [deviceType, setDeviceType] = useState<DeviceType>("DESKTOP");
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [loadingScreens, setLoadingScreens] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isCreatingProject, setIsCreatingProject] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadProjects = useCallback(async () => {
    setLoadingProjects(true);
    setError(null);
    try {
      const data = await fetchApi<StitchProject[]>("/api/ai/stitch/projects", undefined, {
        fallbackMessage: "Failed to load Stitch projects",
      });
      setProjects(data);
      if (!selectedProjectId && data[0]) {
        setSelectedProjectId(data[0].projectId);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load projects");
      setProjects([]);
    } finally {
      setLoadingProjects(false);
    }
  }, [selectedProjectId]);

  const loadScreens = useCallback(async (projectId: string) => {
    if (!projectId) {
      setScreens([]);
      return;
    }

    setLoadingScreens(true);
    try {
      const data = await fetchApi<StitchScreen[]>(`/api/ai/stitch/projects/${projectId}/screens`, undefined, {
        fallbackMessage: "Failed to load screens",
      });
      setScreens(data);
      if (data[0]) {
        setSelectedScreen(data[0]);
      } else {
        setSelectedScreen(null);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load screens");
      setScreens([]);
      setSelectedScreen(null);
    } finally {
      setLoadingScreens(false);
    }
  }, []);

  useEffect(() => {
    void (async () => {
      try {
        await fetchApi("/api/ai/stitch", undefined, { fallbackMessage: "Stitch is not configured" });
        setConfigured(true);
        await loadProjects();
      } catch {
        setConfigured(false);
        setLoadingProjects(false);
      }
    })();
  }, [loadProjects]);

  useEffect(() => {
    if (selectedProjectId) {
      void loadScreens(selectedProjectId);
    }
  }, [selectedProjectId, loadScreens]);

  const handleCreateProject = async () => {
    if (!newProjectTitle.trim()) return;

    setIsCreatingProject(true);
    setError(null);
    try {
      const project = await fetchApi<StitchProject>(
        "/api/ai/stitch/projects",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ title: newProjectTitle.trim() }),
        },
        { fallbackMessage: "Failed to create project" },
      );
      setNewProjectTitle("");
      setProjects((prev) => [project, ...prev]);
      setSelectedProjectId(project.projectId);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create project");
    } finally {
      setIsCreatingProject(false);
    }
  };

  const handleGenerate = async () => {
    if (!selectedProjectId || !prompt.trim()) return;

    setIsGenerating(true);
    setError(null);
    try {
      const result = await fetchApi<{ screen: StitchScreen }>(
        `/api/ai/stitch/projects/${selectedProjectId}/generate`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ prompt: prompt.trim(), deviceType }),
        },
        { fallbackMessage: "Failed to generate screen" },
      );

      setScreens((prev) => [result.screen, ...prev]);
      setSelectedScreen(result.screen);
      setPrompt("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to generate screen");
    } finally {
      setIsGenerating(false);
    }
  };

  const handleEdit = async () => {
    if (!selectedProjectId || !selectedScreen || !prompt.trim()) return;

    setIsGenerating(true);
    setError(null);
    try {
      const edited = await fetchApi<StitchScreen>(
        `/api/ai/stitch/projects/${selectedProjectId}/screens/${selectedScreen.screenId}/edit`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ prompt: prompt.trim(), deviceType }),
        },
        { fallbackMessage: "Failed to edit screen" },
      );

      setScreens((prev) => [edited, ...prev.filter((s) => s.screenId !== edited.screenId)]);
      setSelectedScreen(edited);
      setPrompt("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to edit screen");
    } finally {
      setIsGenerating(false);
    }
  };

  if (configured === false) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Google Stitch not configured</CardTitle>
          <CardDescription>
            Add a Stitch API key to enable UI generation from text prompts.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-3 text-sm text-muted-foreground">
          <p>
            Set <code className="rounded bg-muted px-1 py-0.5">STITCH_API_KEY</code> in your environment, or save a key
            under Settings → Integrations → Google Stitch.
          </p>
          <p>
            Get an API key from{" "}
            <a
              href="https://stitch.withgoogle.com/docs/"
              target="_blank"
              rel="noreferrer"
              className="text-primary underline"
            >
              Google Stitch
            </a>
            .
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid gap-6 xl:grid-cols-[280px_1fr_360px]">
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Projects</CardTitle>
          <CardDescription>Stitch design projects</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-2">
            <Input
              placeholder="New project name"
              value={newProjectTitle}
              onChange={(e) => setNewProjectTitle(e.target.value)}
            />
            <Button type="button" size="icon" onClick={() => void handleCreateProject()} disabled={isCreatingProject}>
              {isCreatingProject ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
            </Button>
          </div>

          {loadingProjects ? (
            <p className="text-sm text-muted-foreground">Loading projects...</p>
          ) : projects.length === 0 ? (
            <p className="text-sm text-muted-foreground">No projects yet. Create one to get started.</p>
          ) : (
            <Select value={selectedProjectId} onValueChange={setSelectedProjectId}>
              <SelectTrigger>
                <SelectValue placeholder="Select project" />
              </SelectTrigger>
              <SelectContent>
                {projects.map((project) => (
                  <SelectItem key={project.projectId} value={project.projectId}>
                    {project.title || project.projectId}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label>Screens</Label>
              <Button type="button" variant="ghost" size="icon" onClick={() => void loadScreens(selectedProjectId)} disabled={!selectedProjectId || loadingScreens}>
                <RefreshCw className={`h-4 w-4 ${loadingScreens ? "animate-spin" : ""}`} />
              </Button>
            </div>
            {loadingScreens ? (
              <p className="text-sm text-muted-foreground">Loading screens...</p>
            ) : screens.length === 0 ? (
              <p className="text-sm text-muted-foreground">No screens in this project.</p>
            ) : (
              <ul className="space-y-1 max-h-72 overflow-y-auto">
                {screens.map((screen) => (
                  <li key={screen.screenId}>
                    <button
                      type="button"
                      onClick={() => setSelectedScreen(screen)}
                      className={`w-full rounded-md border px-3 py-2 text-left text-sm transition-colors ${
                        selectedScreen?.screenId === screen.screenId ? "border-primary bg-accent" : "hover:bg-accent"
                      }`}
                    >
                      Screen {screen.screenId.slice(-6)}
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </CardContent>
      </Card>

      <Card className="min-h-[520px]">
        <CardHeader>
          <CardTitle className="text-base">Preview</CardTitle>
          <CardDescription>Generated UI screenshot and HTML export</CardDescription>
        </CardHeader>
        <CardContent>
          {!selectedScreen ? (
            <div className="flex h-[420px] items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">
              Generate a screen to see the preview
            </div>
          ) : (
            <div className="space-y-4">
              {selectedScreen.imageUrl ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={selectedScreen.imageUrl}
                  alt="Stitch screen preview"
                  className="w-full rounded-lg border bg-muted object-contain max-h-[420px]"
                />
              ) : (
                <div className="flex h-[320px] items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">
                  Screenshot unavailable
                </div>
              )}
              {selectedScreen.htmlUrl ? (
                <Button variant="outline" asChild>
                  <Link href={selectedScreen.htmlUrl} target="_blank" rel="noreferrer">
                    <ExternalLink className="mr-2 h-4 w-4" />
                    Open HTML export
                  </Link>
                </Button>
              ) : null}
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Prompt</CardTitle>
          <CardDescription>Describe the UI you want Stitch to generate or edit</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}

          <div className="space-y-2">
            <Label htmlFor="deviceType">Device</Label>
            <Select value={deviceType} onValueChange={(value) => setDeviceType(value as DeviceType)}>
              <SelectTrigger id="deviceType">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="DESKTOP">
                  <span className="inline-flex items-center gap-2"><Monitor className="h-4 w-4" /> Desktop</span>
                </SelectItem>
                <SelectItem value="MOBILE">
                  <span className="inline-flex items-center gap-2"><Smartphone className="h-4 w-4" /> Mobile</span>
                </SelectItem>
                <SelectItem value="TABLET">
                  <span className="inline-flex items-center gap-2"><Tablet className="h-4 w-4" /> Tablet</span>
                </SelectItem>
                <SelectItem value="AGNOSTIC">Responsive</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="prompt">Prompt</Label>
            <Textarea
              id="prompt"
              rows={8}
              value={prompt}
              onChange={(e) => setPrompt(e.target.value)}
              placeholder="A modern client dashboard with revenue stats, recent activity, and a sidebar navigation..."
            />
          </div>

          <div className="flex flex-col gap-2">
            <Button type="button" onClick={() => void handleGenerate()} disabled={!selectedProjectId || isGenerating || !prompt.trim()}>
              {isGenerating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Sparkles className="mr-2 h-4 w-4" />}
              Generate Screen
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => void handleEdit()}
              disabled={!selectedScreen || isGenerating || !prompt.trim()}
            >
              Edit Selected Screen
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
