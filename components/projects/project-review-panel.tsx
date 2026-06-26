"use client";

import { FormEvent, MouseEvent, useEffect, useMemo, useRef, useState } from "react";
import {
  ExternalLink,
  FileImage,
  Globe,
  Loader2,
  MapPin,
  MessageSquare,
  Plus,
  Upload,
} from "lucide-react";

import { EmptyState } from "@/components/ui/empty-state";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ApiClientError, fetchApi } from "@/lib/api/client";
import { formatFileSize } from "@/lib/storage/utils";

type ReviewComment = {
  id: string;
  reviewItemId: string;
  authorName: string | null;
  authorEmail: string | null;
  body: string;
  xPercent: number | null;
  yPercent: number | null;
  status: string;
  createdAt: string;
};

type ProjectReviewItem = {
  id: string;
  projectId: string;
  type: "website" | "image";
  title: string;
  websiteUrl: string | null;
  imageUrl: string | null;
  imageFileName: string | null;
  imageMimeType: string | null;
  imageSize: number | null;
  status: string;
  createdAt: string;
  comments: ReviewComment[];
};

type PendingPoint = {
  xPercent: number;
  yPercent: number;
};

export function ProjectReviewPanel({ projectId }: { projectId: string }) {
  const [reviews, setReviews] = useState<ProjectReviewItem[]>([]);
  const [activeReviewId, setActiveReviewId] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSavingWebsite, setIsSavingWebsite] = useState(false);
  const [isUploadingImage, setIsUploadingImage] = useState(false);
  const [isSavingComment, setIsSavingComment] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [websiteTitle, setWebsiteTitle] = useState("");
  const [websiteUrl, setWebsiteUrl] = useState("");
  const [imageTitle, setImageTitle] = useState("");
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [pinMode, setPinMode] = useState(false);
  const [pendingPoint, setPendingPoint] = useState<PendingPoint | null>(null);
  const [commentBody, setCommentBody] = useState("");
  const reviewSurfaceRef = useRef<HTMLDivElement | null>(null);

  const activeReview = useMemo(
    () => reviews.find((review) => review.id === activeReviewId) ?? reviews[0] ?? null,
    [activeReviewId, reviews],
  );

  useEffect(() => {
    if (!activeReviewId && reviews.length > 0) {
      setActiveReviewId(reviews[0].id);
    }
  }, [activeReviewId, reviews]);

  async function loadReviews() {
    setIsLoading(true);
    setError(null);

    try {
      const payload = await fetchApi<ProjectReviewItem[]>(
        `/api/projects/${projectId}/reviews`,
        undefined,
        { fallbackMessage: "Failed to load project reviews" },
      );
      setReviews(payload);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Failed to load project reviews");
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    void loadReviews();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [projectId]);

  function mergeReview(review: ProjectReviewItem) {
    setReviews((previous) => {
      const exists = previous.some((item) => item.id === review.id);
      return exists
        ? previous.map((item) => (item.id === review.id ? review : item))
        : [review, ...previous];
    });
    setActiveReviewId(review.id);
  }

  async function handleCreateWebsite(event: FormEvent) {
    event.preventDefault();
    setIsSavingWebsite(true);
    setError(null);

    try {
      const review = await fetchApi<ProjectReviewItem>(
        `/api/projects/${projectId}/reviews`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            type: "website",
            title: websiteTitle,
            websiteUrl,
          }),
        },
        { fallbackMessage: "Failed to add website review" },
      );
      mergeReview(review);
      setWebsiteTitle("");
      setWebsiteUrl("");
    } catch (createError) {
      setError(createError instanceof Error ? createError.message : "Failed to add website review");
    } finally {
      setIsSavingWebsite(false);
    }
  }

  async function handleUploadImage(event: FormEvent) {
    event.preventDefault();
    if (!imageFile) {
      setError("Select an image to upload.");
      return;
    }

    setIsUploadingImage(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append("title", imageTitle);
      formData.append("file", imageFile);

      const review = await fetchApi<ProjectReviewItem>(
        `/api/projects/${projectId}/reviews/upload`,
        {
          method: "POST",
          body: formData,
        },
        { fallbackMessage: "Failed to upload review image" },
      );
      mergeReview(review);
      setImageTitle("");
      setImageFile(null);
    } catch (uploadError) {
      setError(uploadError instanceof Error ? uploadError.message : "Failed to upload review image");
    } finally {
      setIsUploadingImage(false);
    }
  }

  function handleSurfaceClick(event: MouseEvent<HTMLDivElement>) {
    if (!pinMode || !reviewSurfaceRef.current) {
      return;
    }

    const rect = reviewSurfaceRef.current.getBoundingClientRect();
    const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
    const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

    setPendingPoint({
      xPercent: Math.min(100, Math.max(0, xPercent)),
      yPercent: Math.min(100, Math.max(0, yPercent)),
    });
    setPinMode(false);
  }

  async function handleSaveComment(event: FormEvent) {
    event.preventDefault();
    if (!activeReview || !commentBody.trim()) {
      return;
    }

    setIsSavingComment(true);
    setError(null);

    try {
      const comment = await fetchApi<ReviewComment>(
        `/api/projects/${projectId}/reviews/${activeReview.id}/comments`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            body: commentBody,
            xPercent: pendingPoint?.xPercent ?? null,
            yPercent: pendingPoint?.yPercent ?? null,
          }),
        },
        { fallbackMessage: "Failed to save comment" },
      );

      setReviews((previous) =>
        previous.map((review) =>
          review.id === activeReview.id
            ? { ...review, comments: [comment, ...review.comments] }
            : review,
        ),
      );
      setCommentBody("");
      setPendingPoint(null);
      setPinMode(false);
    } catch (commentError) {
      const message =
        commentError instanceof ApiClientError || commentError instanceof Error
          ? commentError.message
          : "Failed to save comment";
      setError(message);
    } finally {
      setIsSavingComment(false);
    }
  }

  const comments = activeReview?.comments ?? [];

  return (
    <div className="space-y-4">
      {error ? (
        <div className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
          {error}
        </div>
      ) : null}

      <div className="grid gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Review Items</CardTitle>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Loading reviews...
                </div>
              ) : reviews.length === 0 ? (
                <EmptyState
                  icon={MessageSquare}
                  title="No review items"
                  description="Add a website or image to start collecting project feedback."
                  className="py-8"
                />
              ) : (
                <div className="space-y-2">
                  {reviews.map((review) => (
                    <button
                      key={review.id}
                      type="button"
                      onClick={() => {
                        setActiveReviewId(review.id);
                        setPendingPoint(null);
                        setPinMode(false);
                      }}
                      className={`w-full rounded-md border px-3 py-2 text-left transition-colors ${
                        activeReview?.id === review.id
                          ? "border-primary bg-primary/5"
                          : "hover:bg-muted/60"
                      }`}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-2">
                          {review.type === "website" ? (
                            <Globe className="h-4 w-4 shrink-0" />
                          ) : (
                            <FileImage className="h-4 w-4 shrink-0" />
                          )}
                          <span className="truncate text-sm font-medium">{review.title}</span>
                        </div>
                        <Badge variant="outline">{review.comments.length}</Badge>
                      </div>
                      <div className="mt-1 truncate text-xs text-muted-foreground">
                        {review.type === "website" ? review.websiteUrl : review.imageFileName}
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Plus className="h-4 w-4" />
                Website
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleCreateWebsite} className="space-y-3">
                <div className="space-y-2">
                  <Label htmlFor="review-website-title">Title</Label>
                  <Input
                    id="review-website-title"
                    value={websiteTitle}
                    onChange={(event) => setWebsiteTitle(event.target.value)}
                    placeholder="Homepage review"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="review-website-url">URL</Label>
                  <Input
                    id="review-website-url"
                    type="url"
                    value={websiteUrl}
                    onChange={(event) => setWebsiteUrl(event.target.value)}
                    placeholder="https://example.com"
                    required
                  />
                </div>
                <Button type="submit" className="w-full" disabled={isSavingWebsite}>
                  {isSavingWebsite ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  Add Website
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Upload className="h-4 w-4" />
                Image
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleUploadImage} className="space-y-3">
                <div className="space-y-2">
                  <Label htmlFor="review-image-title">Title</Label>
                  <Input
                    id="review-image-title"
                    value={imageTitle}
                    onChange={(event) => setImageTitle(event.target.value)}
                    placeholder="Mockup review"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="review-image-file">File</Label>
                  <Input
                    id="review-image-file"
                    type="file"
                    accept="image/*"
                    onChange={(event) => setImageFile(event.target.files?.[0] ?? null)}
                    required
                  />
                  {imageFile ? (
                    <div className="text-xs text-muted-foreground">
                      {imageFile.name} · {formatFileSize(imageFile.size)}
                    </div>
                  ) : null}
                </div>
                <Button type="submit" className="w-full" disabled={isUploadingImage}>
                  {isUploadingImage ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  Upload Image
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-4 2xl:grid-cols-[minmax(0,1fr)_360px]">
          <Card>
            <CardHeader>
              <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <CardTitle className="min-w-0 truncate text-base">
                  {activeReview?.title ?? "Review"}
                </CardTitle>
                <div className="flex items-center gap-2">
                  {activeReview?.type === "website" && activeReview.websiteUrl ? (
                    <Button asChild variant="outline" size="sm">
                      <a href={activeReview.websiteUrl} target="_blank" rel="noreferrer">
                        <ExternalLink className="mr-2 h-4 w-4" />
                        Open
                      </a>
                    </Button>
                  ) : null}
                  <Button
                    type="button"
                    variant={pinMode ? "default" : "outline"}
                    size="sm"
                    disabled={!activeReview}
                    onClick={() => {
                      setPinMode((current) => !current);
                      setPendingPoint(null);
                    }}
                  >
                    <MapPin className="mr-2 h-4 w-4" />
                    Pin Comment
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              {!activeReview ? (
                <EmptyState
                  icon={MessageSquare}
                  title="Select a review item"
                  description="Website and image reviews appear here."
                />
              ) : (
                <div
                  ref={reviewSurfaceRef}
                  onClick={handleSurfaceClick}
                  className="relative min-h-[420px] overflow-hidden rounded-md border bg-muted/40"
                >
                  {activeReview.type === "website" && activeReview.websiteUrl ? (
                    <iframe
                      title={activeReview.title}
                      src={activeReview.websiteUrl}
                      className="h-[640px] w-full bg-background"
                      sandbox="allow-forms allow-popups allow-same-origin allow-scripts"
                    />
                  ) : activeReview.imageUrl ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={activeReview.imageUrl}
                      alt={activeReview.title}
                      className="max-h-[720px] w-full object-contain"
                    />
                  ) : null}

                  {pinMode ? (
                    <div className="absolute inset-0 z-20 cursor-crosshair bg-primary/5" />
                  ) : null}

                  {comments
                    .filter((comment) => comment.xPercent != null && comment.yPercent != null)
                    .map((comment, index) => (
                      <button
                        key={comment.id}
                        type="button"
                        className="absolute z-30 flex h-7 w-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground shadow-lg"
                        style={{
                          left: `${comment.xPercent}%`,
                          top: `${comment.yPercent}%`,
                        }}
                        title={comment.body}
                      >
                        {index + 1}
                      </button>
                    ))}

                  {pendingPoint ? (
                    <div
                      className="absolute z-40 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-amber-500 text-xs font-semibold text-white shadow-lg"
                      style={{
                        left: `${pendingPoint.xPercent}%`,
                        top: `${pendingPoint.yPercent}%`,
                      }}
                    >
                      <MapPin className="h-4 w-4" />
                    </div>
                  ) : null}
                </div>
              )}
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Comment</CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSaveComment} className="space-y-3">
                  <Textarea
                    value={commentBody}
                    onChange={(event) => setCommentBody(event.target.value)}
                    placeholder="Requested change..."
                    rows={4}
                    disabled={!activeReview}
                    required
                  />
                  {pendingPoint ? (
                    <Badge variant="outline">
                      Pin {pendingPoint.xPercent.toFixed(1)}%, {pendingPoint.yPercent.toFixed(1)}%
                    </Badge>
                  ) : null}
                  <div className="flex items-center gap-2">
                    <Button
                      type="submit"
                      disabled={!activeReview || isSavingComment || !commentBody.trim()}
                    >
                      {isSavingComment ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                      Save Comment
                    </Button>
                    {pendingPoint ? (
                      <Button
                        type="button"
                        variant="outline"
                        onClick={() => setPendingPoint(null)}
                      >
                        Clear Pin
                      </Button>
                    ) : null}
                  </div>
                </form>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Comments</CardTitle>
              </CardHeader>
              <CardContent>
                {comments.length === 0 ? (
                  <div className="py-6 text-center text-sm text-muted-foreground">No comments yet</div>
                ) : (
                  <div className="space-y-3">
                    {comments.map((comment, index) => (
                      <div key={comment.id} className="rounded-md border p-3">
                        <div className="mb-2 flex items-center justify-between gap-2">
                          <div className="min-w-0 truncate text-sm font-medium">
                            {comment.authorName || comment.authorEmail || "Client"}
                          </div>
                          {comment.xPercent != null && comment.yPercent != null ? (
                            <Badge variant="outline">Pin {index + 1}</Badge>
                          ) : null}
                        </div>
                        <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                          {comment.body}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
