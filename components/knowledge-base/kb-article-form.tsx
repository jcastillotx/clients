"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { fetchApi } from "@/lib/api/client";

type CategoryOption = { id: string; name: string };

export function KbArticleForm() {
  const router = useRouter();
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    categoryId: "",
    title: "",
    excerpt: "",
    content: "",
    videoUrl: "",
    isPublished: true,
  });

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<Array<{ id: string; name: string }>>("/api/knowledge-base/categories", undefined, {
          fallbackMessage: "Failed to load categories",
        });
        setCategories(data.map((c) => ({ id: c.id, name: c.name })));
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!form.categoryId) {
      setError("Select a category");
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await fetchApi(
        "/api/knowledge-base/articles",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            categoryId: form.categoryId,
            title: form.title,
            excerpt: form.excerpt || null,
            content: form.content,
            videoUrl: form.videoUrl || null,
            isPublished: form.isPublished,
          }),
        },
        { fallbackMessage: "Failed to create article" },
      );
      router.push("/knowledge-base");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create article");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 max-w-3xl">
      {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}

      <div className="space-y-2">
        <Label htmlFor="categoryId">Category *</Label>
        <Select value={form.categoryId} onValueChange={(value) => setForm((p) => ({ ...p, categoryId: value }))} disabled={loading}>
          <SelectTrigger id="categoryId">
            <SelectValue placeholder={loading ? "Loading..." : "Select category"} />
          </SelectTrigger>
          <SelectContent>
            {categories.map((category) => (
              <SelectItem key={category.id} value={category.id}>
                {category.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="space-y-2">
        <Label htmlFor="title">Title *</Label>
        <Input id="title" required value={form.title} onChange={(e) => setForm((p) => ({ ...p, title: e.target.value }))} />
      </div>

      <div className="space-y-2">
        <Label htmlFor="excerpt">Excerpt</Label>
        <Textarea id="excerpt" rows={2} value={form.excerpt} onChange={(e) => setForm((p) => ({ ...p, excerpt: e.target.value }))} />
      </div>

      <div className="space-y-2">
        <Label htmlFor="content">Content *</Label>
        <Textarea id="content" required rows={12} value={form.content} onChange={(e) => setForm((p) => ({ ...p, content: e.target.value }))} />
      </div>

      <div className="space-y-2">
        <Label htmlFor="videoUrl">Video URL</Label>
        <Input id="videoUrl" value={form.videoUrl} onChange={(e) => setForm((p) => ({ ...p, videoUrl: e.target.value }))} />
      </div>

      <div className="flex items-center justify-between rounded-lg border p-4">
        <div>
          <Label htmlFor="published">Published</Label>
          <p className="text-sm text-muted-foreground">Make this article visible in the knowledge base</p>
        </div>
        <Switch id="published" checked={form.isPublished} onCheckedChange={(checked) => setForm((p) => ({ ...p, isPublished: checked }))} />
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Create Article
        </Button>
        <Button type="button" variant="outline" onClick={() => router.push("/knowledge-base")} disabled={isSubmitting}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
