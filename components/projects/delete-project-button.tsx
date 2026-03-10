"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Trash2 } from "lucide-react";

export function DeleteProjectButton({ projectId }: { projectId: string }) {
  const router = useRouter();
  const [deleting, setDeleting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);

  const doDelete = async () => {
    setDeleting(true);
    try {
      const res = await fetch(`/api/projects/${projectId}`, { method: "DELETE" });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error((body as { error?: string }).error || "Failed to delete project");
      }
      router.push("/projects");
    } catch (err) {
      alert(err instanceof Error ? err.message : "Failed to delete project");
      setDeleting(false);
    }
  };

  const handleDelete = () => setConfirmOpen(true);

  return (
    <>
      <Button variant="destructive" size="sm" disabled={deleting} onClick={handleDelete}>
        <Trash2 className="h-4 w-4 mr-2" />
        {deleting ? "Deleting..." : "Delete"}
      </Button>
      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title="Delete project?"
        description="This will permanently delete the project. This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={doDelete}
        loading={deleting}
      />
    </>
  );
}
