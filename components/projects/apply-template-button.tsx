"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { ApplyTemplateDialog } from "@/components/projects/templates/apply-template-dialog";
import { ListChecks } from "lucide-react";

interface ApplyTemplateButtonProps {
  projectId: string;
  projectName: string;
}

export function ApplyTemplateButton({ projectId, projectName }: ApplyTemplateButtonProps) {
  const [open, setOpen] = useState(false);
  const router = useRouter();

  return (
    <>
      <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
        <ListChecks className="h-4 w-4 mr-2" />
        Apply Template
      </Button>
      <ApplyTemplateDialog
        projectId={projectId}
        projectName={projectName}
        open={open}
        onOpenChange={setOpen}
        onApplied={(result) => {
          router.refresh();
          router.push(`/tasks?boardId=${result.boardId}`);
        }}
      />
    </>
  );
}
