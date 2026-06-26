import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { GET, POST } from "@/app/api/projects/[id]/reviews/route";
import { POST as POST_COMMENT } from "@/app/api/projects/[id]/reviews/[reviewId]/comments/route";
import { db } from "@/lib/db";
import { requireProjectReviewAccess } from "@/lib/projects/review-access";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/projects/review-access", () => ({
  requireProjectReviewAccess: vi.fn(),
}));

vi.mock("@/lib/db", () => ({
  db: {
    select: vi.fn(),
    insert: vi.fn(),
  },
}));

const projectId = "11111111-1111-1111-1111-111111111111";
const reviewId = "22222222-2222-2222-2222-222222222222";

const routeContext = {
  params: Promise.resolve({ id: projectId }),
};

const commentRouteContext = {
  params: Promise.resolve({ id: projectId, reviewId }),
};

function mockAccess() {
  vi.mocked(requireProjectReviewAccess).mockResolvedValue({
    user: {
      id: "user-1",
      email: "client@example.com",
      user_metadata: { name: "Client User" },
    } as never,
    project: {
      id: projectId,
      clientId: "client-1",
      name: "Website Redesign",
    },
    access: {
      clientId: "client-1",
      isAdmin: false,
    },
  });
}

describe("project review routes", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockAccess();
  });

  it("lists review items with comments", async () => {
    vi.mocked(db.select)
      .mockReturnValueOnce({
        from: vi.fn().mockReturnValue({
          where: vi.fn().mockReturnValue({
            orderBy: vi.fn().mockResolvedValue([
              {
                id: reviewId,
                projectId,
                type: "website",
                title: "Homepage",
                websiteUrl: "https://example.com",
                imageFileName: null,
                imageMimeType: null,
                imageSize: null,
                status: "open",
                createdAt: new Date("2026-06-26T10:00:00Z"),
                updatedAt: new Date("2026-06-26T10:00:00Z"),
              },
            ]),
          }),
        }),
      } as never)
      .mockReturnValueOnce({
        from: vi.fn().mockReturnValue({
          leftJoin: vi.fn().mockReturnValue({
            where: vi.fn().mockReturnValue({
              orderBy: vi.fn().mockResolvedValue([
                {
                  id: "comment-1",
                  reviewItemId: reviewId,
                  projectId,
                  authorId: "user-1",
                  authorName: "Client User",
                  authorEmail: "client@example.com",
                  body: "Move this headline up.",
                  xPercent: "45.000",
                  yPercent: "20.000",
                  status: "open",
                  createdAt: new Date("2026-06-26T10:01:00Z"),
                  updatedAt: new Date("2026-06-26T10:01:00Z"),
                },
              ]),
            }),
          }),
        }),
      } as never);

    const response = (await GET(
      new NextRequest(`http://localhost/api/projects/${projectId}/reviews`),
      routeContext,
    )) as Response;
    const body = await readJson<{ success: boolean; data: Array<{ comments: unknown[] }> }>(
      response,
    );

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data).toHaveLength(1);
    expect(body.data[0].comments).toHaveLength(1);
  });

  it("creates a website review item", async () => {
    vi.mocked(db.insert).mockReturnValue({
      values: vi.fn().mockReturnValue({
        returning: vi.fn().mockResolvedValue([
          {
            id: reviewId,
            projectId,
            type: "website",
            title: "Homepage",
            websiteUrl: "https://example.com",
            imageFileName: null,
            imageMimeType: null,
            imageSize: null,
            status: "open",
            createdAt: new Date("2026-06-26T10:00:00Z"),
            updatedAt: new Date("2026-06-26T10:00:00Z"),
          },
        ]),
      }),
    } as never);

    const response = (await POST(
      jsonRequest(`http://localhost/api/projects/${projectId}/reviews`, {
        type: "website",
        title: "Homepage",
        websiteUrl: "https://example.com",
      }) as NextRequest,
      routeContext,
    )) as Response;
    const body = await readJson<{ success: boolean; data: { id: string } }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.id).toBe(reviewId);
  });

  it("creates a pinned comment", async () => {
    vi.mocked(db.select).mockReturnValue({
      from: vi.fn().mockReturnValue({
        where: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue([{ id: reviewId }]),
        }),
      }),
    } as never);
    vi.mocked(db.insert).mockReturnValue({
      values: vi.fn().mockReturnValue({
        returning: vi.fn().mockResolvedValue([
          {
            id: "comment-1",
            reviewItemId: reviewId,
            projectId,
            authorId: "user-1",
            body: "Use the alternate hero image.",
            xPercent: "25.000",
            yPercent: "40.000",
            status: "open",
            createdAt: new Date("2026-06-26T10:01:00Z"),
            updatedAt: new Date("2026-06-26T10:01:00Z"),
          },
        ]),
      }),
    } as never);

    const response = (await POST_COMMENT(
      jsonRequest(`http://localhost/api/projects/${projectId}/reviews/${reviewId}/comments`, {
        body: "Use the alternate hero image.",
        xPercent: 25,
        yPercent: 40,
      }) as NextRequest,
      commentRouteContext,
    )) as Response;
    const body = await readJson<{
      success: boolean;
      data: { xPercent: number | null; yPercent: number | null };
    }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.xPercent).toBe(25);
    expect(body.data.yPercent).toBe(40);
  });
});
