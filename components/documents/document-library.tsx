"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Upload, Search, Grid, List } from "lucide-react";
import { DocumentGrid } from "./document-grid";
import { DocumentListView } from "./document-list-view";
import { UploadDialog } from "./upload-dialog";

interface Client {
  id: string;
  company_name: string;
}

interface Folder {
  id: string;
  name: string;
  description: string | null;
  client_id: string;
  type: string | null;
  created_at: string;
}

interface Document {
  id: string;
  name: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  storage_path: string;
  storage_url: string | null;
  client_id: string;
  folder_id: string | null;
  version: number;
  is_latest_version: boolean;
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

interface DocumentLibraryProps {
  initialDocuments: Document[];
  initialFolders: Folder[];
  clients: Client[];
  canUpload: boolean;
  initialClientId?: string;
  initialRequestId?: string;
}

export function DocumentLibrary({
  initialDocuments,
  initialFolders,
  clients,
  canUpload,
  initialClientId,
}: DocumentLibraryProps) {
  const [documents, setDocuments] = useState(initialDocuments);
  const [folders] = useState(initialFolders);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedClient, setSelectedClient] = useState(initialClientId || "all");
  const [selectedFolder, setSelectedFolder] = useState("all");
  const [viewMode, setViewMode] = useState<"grid" | "list">("grid");
  const [uploadDialogOpen, setUploadDialogOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  // Filter documents based on search, client, and folder
  const filteredDocuments = documents.filter((doc) => {
    const matchesSearch =
      doc.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      doc.file_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      doc.tags?.some((tag) => tag.toLowerCase().includes(searchQuery.toLowerCase()));

    const matchesClient = selectedClient === "all" || doc.client_id === selectedClient;
    const matchesFolder = selectedFolder === "all" || doc.folder_id === selectedFolder;

    return matchesSearch && matchesClient && matchesFolder;
  });

  const filteredFolders = folders.filter((folder) => {
    return selectedClient === "all" || folder.client_id === selectedClient;
  });

  const handleUploadSuccess = (newDocument: Document) => {
    setDocuments((prev) => [newDocument, ...prev]);
    setUploadDialogOpen(false);
  };

  const handleDelete = async (documentId: string) => {
    setIsLoading(true);
    try {
      const response = await fetch(`/api/documents/${documentId}`, {
        method: "DELETE",
      });

      if (!response.ok) throw new Error("Failed to delete document");

      setDocuments((prev) => prev.filter((doc) => doc.id !== documentId));
    } catch (error) {
      console.error("Error deleting document:", error);
      alert("Failed to delete document");
    } finally {
      setIsLoading(false);
    }
  };

  const handleDownload = async (documentId: string) => {
    try {
      const response = await fetch(`/api/documents/${documentId}/download`);
      if (!response.ok) throw new Error("Failed to generate download URL");

      const { url, fileName } = await response.json();

      // Create temporary link and trigger download
      const link = document.createElement("a");
      link.href = url;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (error) {
      console.error("Error downloading document:", error);
      alert("Failed to download document");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Documents</h1>
          <p className="text-muted-foreground">Manage and organize your documents and files</p>
        </div>
        {canUpload && (
          <Button onClick={() => setUploadDialogOpen(true)}>
            <Upload className="mr-2 h-4 w-4" />
            Upload Document
          </Button>
        )}
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search documents..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9"
          />
        </div>
        <Select value={selectedClient} onValueChange={(val) => {
          setSelectedClient(val);
          setSelectedFolder("all"); // Reset folder when client changes
        }}>
          <SelectTrigger className="w-full sm:w-[200px]">
            <SelectValue placeholder="All clients" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All clients</SelectItem>
            {clients.map((client) => (
              <SelectItem key={client.id} value={client.id}>
                {client.company_name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={selectedFolder} onValueChange={setSelectedFolder}>
          <SelectTrigger className="w-full sm:w-[200px]">
            <SelectValue placeholder="All folders" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All folders</SelectItem>
            {filteredFolders.map((folder) => (
              <SelectItem key={folder.id} value={folder.id}>
                {folder.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Tabs value={viewMode} onValueChange={(v) => setViewMode(v as "grid" | "list")}>
          <TabsList>
            <TabsTrigger value="grid">
              <Grid className="h-4 w-4" />
            </TabsTrigger>
            <TabsTrigger value="list">
              <List className="h-4 w-4" />
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>

      {/* Documents */}
      {filteredDocuments.length === 0 ? (
        <div className="flex min-h-[400px] flex-col items-center justify-center rounded-lg border border-dashed">
          <Upload className="mb-4 h-12 w-12 text-muted-foreground" />
          <h3 className="mb-2 text-lg font-semibold">No documents found</h3>
          <p className="mb-4 text-sm text-muted-foreground">
            {searchQuery || selectedClient !== "all"
              ? "Try adjusting your filters"
              : "Upload your first document to get started"}
          </p>
          {canUpload && !searchQuery && selectedClient === "all" && (
            <Button onClick={() => setUploadDialogOpen(true)}>
              <Upload className="mr-2 h-4 w-4" />
              Upload Document
            </Button>
          )}
        </div>
      ) : viewMode === "grid" ? (
        <DocumentGrid
          documents={filteredDocuments}
          onDelete={handleDelete}
          onDownload={handleDownload}
          isLoading={isLoading}
        />
      ) : (
        <DocumentListView
          documents={filteredDocuments}
          onDelete={handleDelete}
          onDownload={handleDownload}
          isLoading={isLoading}
        />
      )}

      {/* Upload Dialog */}
      <UploadDialog
        open={uploadDialogOpen}
        onOpenChange={setUploadDialogOpen}
        clients={clients}
        onSuccess={handleUploadSuccess}
        defaultClientId={selectedClient !== "all" ? selectedClient : undefined}
      />
    </div>
  );
}
