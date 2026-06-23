"use client";

import { useCallback, useEffect, useState } from "react";
import { Loader2, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { fetchApi } from "@/lib/api/client";

type KbCategory = {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  articleCount?: number;
};

export function KbCategoryManager() {
  const [categories, setCategories] = useState<KbCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");

  const loadCategories = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchApi<KbCategory[]>("/api/knowledge-base/categories", undefined, {
        fallbackMessage: "Failed to load categories",
      });
      setCategories(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load categories");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadCategories();
  }, [loadCategories]);

  const handleCreate = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      await fetchApi(
        "/api/knowledge-base/categories",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name, description: description || null }),
        },
        { fallbackMessage: "Failed to create category" },
      );
      setName("");
      setDescription("");
      await loadCategories();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create category");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm("Delete this category?")) return;

    try {
      await fetchApi(
        `/api/knowledge-base/categories/${id}`,
        { method: "DELETE" },
        { fallbackMessage: "Failed to delete category" },
      );
      await loadCategories();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to delete category");
    }
  };

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Add Category</CardTitle>
          <CardDescription>Organize knowledge base articles into categories.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleCreate} className="space-y-4">
            {error ? <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{error}</div> : null}
            <div className="space-y-2">
              <Label htmlFor="name">Name *</Label>
              <Input id="name" required value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" rows={3} value={description} onChange={(e) => setDescription(e.target.value)} />
            </div>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Create Category
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Existing Categories</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : categories.length === 0 ? (
            <p className="text-sm text-muted-foreground">No categories yet.</p>
          ) : (
            <ul className="space-y-2">
              {categories.map((category) => (
                <li key={category.id} className="flex items-center justify-between rounded-md border p-3">
                  <div>
                    <p className="font-medium">{category.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {category.articleCount ?? 0} articles
                      {category.description ? ` · ${category.description}` : ""}
                    </p>
                  </div>
                  <Button type="button" variant="ghost" size="icon" onClick={() => void handleDelete(category.id)}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
