import { Stitch, StitchToolClient } from "@google/stitch-sdk";
import type { Screen } from "@google/stitch-sdk";
import { resolveStitchApiKey } from "@/lib/stitch/resolve-api-key";

export type StitchDeviceType = "MOBILE" | "DESKTOP" | "TABLET" | "AGNOSTIC";

export type SerializedStitchScreen = {
  id: string;
  screenId: string;
  projectId: string;
  htmlUrl: string | null;
  imageUrl: string | null;
};

export type SerializedStitchProject = {
  id: string;
  projectId: string;
  title?: string;
};

export async function createStitchSdk(clientId: string | null): Promise<Stitch | null> {
  const apiKey = await resolveStitchApiKey(clientId);
  if (!apiKey) {
    return null;
  }

  const toolClient = new StitchToolClient({ apiKey });
  return new Stitch(toolClient);
}

export async function serializeScreen(screen: Screen): Promise<SerializedStitchScreen> {
  const [htmlUrl, imageUrl] = await Promise.all([
    screen.getHtml().catch(() => null),
    screen.getImage().catch(() => null),
  ]);

  return {
    id: screen.id,
    screenId: screen.screenId,
    projectId: screen.projectId,
    htmlUrl,
    imageUrl,
  };
}

export function serializeProject(project: { id: string; projectId: string; data?: { title?: string } }): SerializedStitchProject {
  return {
    id: project.id,
    projectId: project.projectId,
    title: typeof project.data?.title === "string" ? project.data.title : undefined,
  };
}
