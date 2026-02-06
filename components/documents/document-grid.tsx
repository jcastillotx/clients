"use client";

import { Card, CardContent, CardFooter } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { FileText, Image, FileArchive, File, Download, MoreVertical, Trash2, Share2, Eye } from "lucide-react";
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

interface DocumentGridProps {
  documents: Document[];
  onDelete: (documentId: string) => void;
  onDownload: (documentId: string) => void;
  isLoading: boolean;
}

function getFileIcon(mimeType: string) {
  if (mimeType.startsWith("image/")) {
    return <Image className="h-12 w-12 text-blue-500" />;
  } else if (mimeType.includes("pdf")) {
    return <FileText className="h-12 w-12 text-red-500" />;
  } else if (mimeType.includes("zip") || mimeType.includes("archive")) {
    return <FileArchive className="h-12 w-12 text-yellow-500" />;
  }
  return <File className="h-12 w-12 text-gray-500" />;
}

export function DocumentGrid({ documents, onDelete, onDownload, isLoading }: DocumentGridProps) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {documents.map((doc) => (
        <Card key={doc.id} className="overflow-hidden">
          <CardContent className="p-6">
            <div className="mb-4 flex items-center justify-between">
              <div className="flex items-center justify-center rounded-lg bg-muted p-3">
                {getFileIcon(doc.mime_type)}
              </div>
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
                    onClick={() => {
                      if (confirm("Are you sure you want to delete this document?")) {
                        onDelete(doc.id);
                      }
                    }}
                    className="text-destructive"
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            <div className="space-y-2">
              <h3 className="truncate font-semibold" title={doc.name}>
                {doc.name}
              </h3>
              <p className="truncate text-sm text-muted-foreground" title={doc.file_name}>
                {doc.file_name}
              </p>
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <span>{formatFileSize(doc.file_size)}</span>
                <span>•</span>
                <span>v{doc.version}</span>
              </div>
              {doc.tags && doc.tags.length > 0 && (
                <div className="flex flex-wrap gap-1">
                  {doc.tags.slice(0, 3).map((tag) => (
                    <Badge key={tag} variant="secondary" className="text-xs">
                      {tag}
                    </Badge>
                  ))}
                  {doc.tags.length > 3 && (
                    <Badge variant="secondary" className="text-xs">
                      +{doc.tags.length - 3}
                    </Badge>
                  )}
                </div>
              )}
            </div>
          </CardContent>

          <CardFooter className="border-t bg-muted/50 p-4 text-xs text-muted-foreground">
            <div className="flex w-full items-center justify-between">
              <span>{doc.client?.company_name}</span>
              <span title={new Date(doc.created_at).toLocaleString()}>
                {formatDistanceToNow(new Date(doc.created_at), { addSuffix: true })}
              </span>
            </div>
          </CardFooter>
        </Card>
      ))}
    </div>
  );
}
