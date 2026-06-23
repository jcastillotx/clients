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

export function StaffGuideForm() {
  const router = useRouter();
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    categoryId: "",
    title: "",
    summary: "",
    content: "",
    serviceTier: "",
    price: "",
    commitment: "",
    isInternal: true,
    isPublished: true,
  });

  useEffect(() => {
    void (async () => {
      try {
        const data = await fetchApi<Array<{ id: string; name: string }>>("/api/staff-guides/categories", undefined, {
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
        "/api/staff-guides",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            categoryId: form.categoryId,
            title: form.title,
            summary: form.summary || null,
            content: form.content,
            serviceTier: form.serviceTier || null,
            price: form.price ? Number(form.price) : null,
            commitment: form.commitment || null,
            isInternal: form.isInternal,
            isPublished: form.isPublished,
          }),
        },
        { fallbackMessage: "Failed to create guide" },
      );
      router.push("/staff-guides");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create guide");
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
        <Label htmlFor="summary">Summary</Label>
        <Textarea id="summary" rows={2} value={form.summary} onChange={(e) => setForm((p) => ({ ...p, summary: e.target.value }))} />
      </div>

      <div className="space-y-2">
        <Label htmlFor="content">Content *</Label>
        <Textarea id="content" required rows={12} value={form.content} onChange={(e) => setForm((p) => ({ ...p, content: e.target.value }))} />
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <div className="space-y-2">
          <Label htmlFor="serviceTier">Service Tier</Label>
          <Input id="serviceTier" value={form.serviceTier} onChange={(e) => setForm((p) => ({ ...p, serviceTier: e.target.value }))} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="price">Price</Label>
          <Input id="price" type="number" step="0.01" value={form.price} onChange={(e) => setForm((p) => ({ ...p, price: e.target.value }))} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="commitment">Commitment</Label>
          <Input id="commitment" value={form.commitment} onChange={(e) => setForm((p) => ({ ...p, commitment: e.target.value }))} />
        </div>
      </div>

      <div className="flex flex-col gap-3">
        <div className="flex items-center justify-between rounded-lg border p-4">
          <Label htmlFor="internal">Internal only</Label>
          <Switch id="internal" checked={form.isInternal} onCheckedChange={(checked) => setForm((p) => ({ ...p, isInternal: checked }))} />
        </div>
        <div className="flex items-center justify-between rounded-lg border p-4">
          <Label htmlFor="published">Published</Label>
          <Switch id="published" checked={form.isPublished} onCheckedChange={(checked) => setForm((p) => ({ ...p, isPublished: checked }))} />
        </div>
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Create Guide
        </Button>
        <Button type="button" variant="outline" onClick={() => router.push("/staff-guides")} disabled={isSubmitting}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
