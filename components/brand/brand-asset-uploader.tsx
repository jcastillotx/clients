"use client";

import { useState } from "react";
import { Upload, X, FileIcon, ImageIcon } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";

export function BrandAssetUploader() {
  const [files, setFiles] = useState<any[]>([]);

  const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files).map((file) => ({
        id: Math.random().toString(36).substr(2, 9),
        name: file.name,
        size: file.size,
        type: file.type,
        progress: 100, // Mock progress
      }));
      setFiles([...files, ...newFiles]);
    }
  };

  const removeFile = (id: string) => {
    setFiles(files.filter((f) => f.id !== id));
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium">Brand Assets</h3>
      </div>

      <div className="border-2 border-dashed rounded-lg p-8 text-center space-y-4 bg-muted/30">
        <div className="flex justify-center">
          <Upload className="h-10 w-10 text-muted-foreground" />
        </div>
        <div className="space-y-1">
          <p className="text-sm font-medium">Drag & drop files here or click to browse</p>
          <p className="text-xs text-muted-foreground">Logos, icons, and other brand assets (PNG, SVG, PDF up to 10MB)</p>
        </div>
        <Input
          type="file"
          className="hidden"
          id="asset-upload"
          multiple
          onChange={onFileChange}
          accept="image/*,application/pdf"
        />
        <Button asChild variant="outline">
          <label htmlFor="asset-upload" className="cursor-pointer">
            Select Files
          </label>
        </Button>
      </div>

      <div className="grid gap-3">
        {files.map((file) => (
          <Card key={file.id}>
            <CardContent className="p-3 flex items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-primary/10 rounded">
                  {file.type.includes("image") ? (
                    <ImageIcon className="h-5 w-5 text-primary" />
                  ) : (
                    <FileIcon className="h-5 w-5 text-primary" />
                  )}
                </div>
                <div className="space-y-0.5">
                  <p className="text-sm font-medium truncate max-w-[200px]">{file.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {(file.size / 1024).toFixed(1)} KB
                  </p>
                </div>
              </div>
              
              <div className="flex items-center gap-4 flex-1 justify-end">
                <div className="w-24">
                  <Progress value={file.progress} className="h-1.5" />
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => removeFile(file.id)}
                  className="h-8 w-8 text-muted-foreground hover:text-destructive"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

// Minimal Input component for local use if UI/input is not available, but we know it's there
function Input({ className, ...props }: React.InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={`flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${className}`}
      {...props}
    />
  );
}
