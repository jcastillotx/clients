"use client";

import { useState, useRef } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { fetchApi } from "@/lib/api/client";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Upload, X, FileIcon } from "lucide-react";
import { Progress } from "@/components/ui/progress";
import { formatFileSize } from "@/lib/storage/utils";

interface Client {
  id: string;
  company_name: string;
}

interface UploadDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  clients: Client[];
  onSuccess: (document: any) => void;
  defaultClientId?: string;
}

export function UploadDialog({ open, onOpenChange, clients, onSuccess, defaultClientId }: UploadDialogProps) {
  const [file, setFile] = useState<File | null>(null);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [clientId, setClientId] = useState(defaultClientId || "");
  const [tags, setTags] = useState("");
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [isDragging, setIsDragging] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = (selectedFile: File | null) => {
    if (selectedFile) {
      setFile(selectedFile);
      if (!name) {
        // Auto-populate name from filename (without extension)
        const nameWithoutExt = selectedFile.name.replace(/\.[^/.]+$/, "");
        setName(nameWithoutExt);
      }
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile) {
      handleFileChange(droppedFile);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!file || !name || !clientId) {
      setUploadError("Please fill in all required fields");
      return;
    }

    setIsUploading(true);
    setUploadProgress(0);
    setUploadError(null);

    const progressInterval = setInterval(() => {
      setUploadProgress((prev) => Math.min(prev + 10, 90));
    }, 100);

    try {
      const formData = new FormData();
      formData.append("file", file);
      formData.append("name", name);
      formData.append("clientId", clientId);
      if (description) formData.append("description", description);
      if (tags) {
        const tagArray = tags.split(",").map((tag) => tag.trim());
        formData.append("tags", JSON.stringify(tagArray));
      }

      // Simulate progress for better UX (actual upload happens instantly for small files)

      const document = await fetchApi<Record<string, unknown>>(
        "/api/documents/upload",
        { method: "POST", body: formData },
        { fallbackMessage: "Failed to upload document" },
      );

      clearInterval(progressInterval);
      setUploadProgress(100);

      // Reset form
      setFile(null);
      setName("");
      setDescription("");
      setTags("");
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }

      onSuccess(document);
    } catch (error) {
      console.error("Upload error:", error);
      setUploadError(error instanceof Error ? error.message : "Failed to upload document");
    } finally {
      clearInterval(progressInterval);
      setIsUploading(false);
      setUploadProgress(0);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>Upload Document</DialogTitle>
            <DialogDescription>
              Upload a new document to the library. Files up to 100MB are supported.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            {uploadError ? (
              <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
                {uploadError}
              </div>
            ) : null}

            {/* File Upload */}
            <div className="space-y-2">
              <Label>File *</Label>
              {!file ? (
                <div
                  className={`flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed transition-colors ${
                    isDragging ? "border-primary bg-primary/5" : "border-muted-foreground/25"
                  }`}
                  onDragOver={handleDragOver}
                  onDragLeave={handleDragLeave}
                  onDrop={handleDrop}
                  onClick={() => fileInputRef.current?.click()}
                >
                  <Upload className="mb-4 h-10 w-10 text-muted-foreground" />
                  <p className="mb-2 text-sm font-medium">Drop your file here, or click to browse</p>
                  <p className="text-xs text-muted-foreground">
                    Supported formats: PDF, Images, Documents, Archives (max 100MB)
                  </p>
                  <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    onChange={(e) => handleFileChange(e.target.files?.[0] || null)}
                    disabled={isUploading}
                  />
                </div>
              ) : (
                <div className="flex items-center justify-between rounded-lg border p-4">
                  <div className="flex items-center gap-3">
                    <FileIcon className="h-10 w-10 text-primary" />
                    <div>
                      <p className="font-medium">{file.name}</p>
                      <p className="text-sm text-muted-foreground">{formatFileSize(file.size)}</p>
                    </div>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setFile(null);
                      if (fileInputRef.current) {
                        fileInputRef.current.value = "";
                      }
                    }}
                    disabled={isUploading}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              )}
            </div>

            {/* Document Name */}
            <div className="space-y-2">
              <Label htmlFor="name">Document Name *</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Enter document name"
                disabled={isUploading}
                required
              />
            </div>

            {/* Client Selection */}
            <div className="space-y-2">
              <Label htmlFor="client">Client *</Label>
              <Select value={clientId} onValueChange={setClientId} disabled={isUploading} required>
                <SelectTrigger id="client">
                  <SelectValue placeholder="Select a client" />
                </SelectTrigger>
                <SelectContent>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Description */}
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Enter document description"
                rows={3}
                disabled={isUploading}
              />
            </div>

            {/* Tags */}
            <div className="space-y-2">
              <Label htmlFor="tags">Tags</Label>
              <Input
                id="tags"
                value={tags}
                onChange={(e) => setTags(e.target.value)}
                placeholder="contract, legal, signed (comma-separated)"
                disabled={isUploading}
              />
              <p className="text-xs text-muted-foreground">Separate multiple tags with commas</p>
            </div>

            {/* Upload Progress */}
            {isUploading && (
              <div className="space-y-2">
                <div className="flex items-center justify-between text-sm">
                  <span>Uploading...</span>
                  <span>{uploadProgress}%</span>
                </div>
                <Progress value={uploadProgress} />
              </div>
            )}
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isUploading}>
              Cancel
            </Button>
            <Button type="submit" disabled={!file || !name || !clientId || isUploading}>
              {isUploading ? "Uploading..." : "Upload"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
