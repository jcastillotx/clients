"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { fetchApi } from "@/lib/api/client";
import { Trash2 } from "lucide-react";

export function DeleteProjectRequestButton({ requestId }: { requestId: string }) {
  const router = useRouter();
  const [deleting, setDeleting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await fetchApi(`/api/projects/requests/${requestId}`, { method: "DELETE" }, {
        fallbackMessage: "Failed to delete project request",
      });
      router.push("/projects/requests");
    } catch (err) {
      alert(err instanceof Error ? err.message : "Failed to delete project request");
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
        title="Delete project request?"
        description="This will permanently delete the project request. This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={doDelete}
        loading={deleting}
      />
    </>
  );
}
