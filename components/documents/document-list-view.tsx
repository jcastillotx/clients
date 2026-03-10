"use client";

import { useState } from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Download, MoreVertical, Trash2, Share2, Eye } from "lucide-react";
import { formatFileSize } from "@/lib/storage/utils";
import { formatDistanceToNow } from "date-fns";

interface Document {
  id: string;
  name: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  version: number;
  tags: string[] | null;
  created_at: string;
  client: {
    id: string;
    company_name: string;
  } | null;
  uploader: {
    id: string;
    name: string;
    email: string;
  } | null;
}

interface DocumentListViewProps {
  documents: Document[];
  onDelete: (documentId: string) => void;
  onDownload: (documentId: string) => void;
  isLoading: boolean;
}

export function DocumentListView({ documents, onDelete, onDownload, isLoading }: DocumentListViewProps) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [pendingDeleteId, setPendingDeleteId] = useState<string | null>(null);

  const handleDeleteClick = (documentId: string) => {
    setPendingDeleteId(documentId);
    setConfirmOpen(true);
  };

  const doDelete = () => {
    if (pendingDeleteId) {
      onDelete(pendingDeleteId);
      setPendingDeleteId(null);
    }
  };

  return (
    <>
    <div className="rounded-lg border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>File Name</TableHead>
            <TableHead>Client</TableHead>
            <TableHead>Size</TableHead>
            <TableHead>Version</TableHead>
            <TableHead>Tags</TableHead>
            <TableHead>Uploaded</TableHead>
            <TableHead className="w-[50px]"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {documents.length === 0 ? (
            <TableRow>
              <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                No documents found
              </TableCell>
            </TableRow>
          ) : (
            documents.map((doc) => (
              <TableRow key={doc.id}>
                <TableCell className="font-medium">
                  <div className="max-w-[200px] truncate" title={doc.name}>
                    {doc.name}
                  </div>
                </TableCell>
                <TableCell>
                  <div className="max-w-[200px] truncate text-muted-foreground" title={doc.file_name}>
                    {doc.file_name}
                  </div>
                </TableCell>
                <TableCell>
                  <div className="max-w-[150px] truncate">{doc.client?.company_name}</div>
                </TableCell>
                <TableCell className="text-muted-foreground">{formatFileSize(doc.file_size)}</TableCell>
                <TableCell className="text-muted-foreground">v{doc.version}</TableCell>
                <TableCell>
                  {doc.tags && doc.tags.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                      {doc.tags.slice(0, 2).map((tag) => (
                        <Badge key={tag} variant="secondary" className="text-xs">
                          {tag}
                        </Badge>
                      ))}
                      {doc.tags.length > 2 && (
                        <Badge variant="secondary" className="text-xs">
                          +{doc.tags.length - 2}
                        </Badge>
                      )}
                    </div>
                  ) : (
                    <span className="text-muted-foreground">—</span>
                  )}
                </TableCell>
                <TableCell className="text-muted-foreground">
                  <div title={new Date(doc.created_at).toLocaleString()}>
                    {formatDistanceToNow(new Date(doc.created_at), { addSuffix: true })}
                  </div>
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" disabled={isLoading}>
                        <MoreVertical className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onClick={() => onDownload(doc.id)}>
                        <Download className="mr-2 h-4 w-4" />
                        Download
                      </DropdownMenuItem>
                      <DropdownMenuItem>
                        <Eye className="mr-2 h-4 w-4" />
                        Preview
                      </DropdownMenuItem>
                      <DropdownMenuItem>
                        <Share2 className="mr-2 h-4 w-4" />
                        Share
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        onClick={() => handleDeleteClick(doc.id)}
                        className="text-destructive"
                      >
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </div>
    <ConfirmDialog
      open={confirmOpen}
      onOpenChange={setConfirmOpen}
      title="Delete document?"
      description="This will permanently delete the document. This action cannot be undone."
      confirmLabel="Delete"
      onConfirm={doDelete}
    />
    </>
  );
}
