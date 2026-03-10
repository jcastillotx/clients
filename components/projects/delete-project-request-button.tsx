"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Trash2 } from "lucide-react";

export function DeleteProjectRequestButton({ requestId }: { requestId: string }) {
  const router = useRouter();
  const [deleting, setDeleting] = useState(false);

  const handleDelete = async () => {
    if (!confirm("Delete this project request? This cannot be undone.")) return;
    setDeleting(true);
    try {
      const res = await fetch(`/api/projects/requests/${requestId}`, { method: "DELETE" });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error((body as { error?: string }).error || "Failed to delete project request");
      }
      router.push("/projects/requests");
    } catch (err) {
      alert(err instanceof Error ? err.message : "Failed to delete project request");
      setDeleting(false);
    }
  };

  return (
    <Button variant="destructive" size="sm" disabled={deleting} onClick={handleDelete}>
      <Trash2 className="h-4 w-4 mr-2" />
      {deleting ? "Deleting..." : "Delete"}
    </Button>
  );
}
