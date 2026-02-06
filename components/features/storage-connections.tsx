"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Cloud, Plus, Trash2, RefreshCw } from "lucide-react";
import { toast } from "sonner";

interface StorageConnection {
  id: string;
  provider: string;
  connectionName: string;
  syncEnabled: boolean;
  lastSyncAt: string | null;
  config: {
    bucket?: string;
    region?: string;
    path?: string;
    autoSync?: boolean;
    syncInterval?: number;
  } | null;
  createdAt: string;
}

interface StorageConnectionsProps {
  clientId: string;
}

export function StorageConnections({ clientId }: StorageConnectionsProps) {
  const [connections, setConnections] = useState<StorageConnection[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    provider: "",
    connectionName: "",
    bucket: "",
    region: "",
    accessKey: "",
    secretKey: "",
  });

  useEffect(() => {
    fetchConnections();
  }, [clientId]);

  const fetchConnections = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/storage-connections?clientId=${clientId}`);

      if (!response.ok) throw new Error("Failed to fetch connections");

      const data = await response.json();
      setConnections(data);
    } catch (error) {
      toast.error("Failed to load storage connections");
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async () => {
    try {
      const response = await fetch("/api/storage-connections", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          provider: formData.provider,
          connectionName: formData.connectionName,
          credentials: {
            accessKey: formData.accessKey,
            secretKey: formData.secretKey,
          },
          config: {
            bucket: formData.bucket,
            region: formData.region,
            autoSync: true,
            syncInterval: 60,
          },
        }),
      });

      if (!response.ok) throw new Error("Failed to create connection");

      toast.success("Storage connection created");
      setDialogOpen(false);
      fetchConnections();
      setFormData({
        provider: "",
        connectionName: "",
        bucket: "",
        region: "",
        accessKey: "",
        secretKey: "",
      });
    } catch (error) {
      toast.error("Failed to create connection");
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Are you sure you want to delete this connection?")) return;

    try {
      const response = await fetch(`/api/storage-connections?id=${id}`, {
        method: "DELETE",
      });

      if (!response.ok) throw new Error("Failed to delete connection");

      toast.success("Connection deleted");
      fetchConnections();
    } catch (error) {
      toast.error("Failed to delete connection");
    }
  };

  const getProviderIcon = (provider: string) => {
    return <Cloud className="h-5 w-5" />;
  };

  if (loading) {
    return <div className="text-center p-8">Loading connections...</div>;
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Storage Connections</h2>
          <p className="text-gray-500">Manage external storage integrations</p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Add Connection
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>New Storage Connection</DialogTitle>
              <DialogDescription>Connect an external storage provider to sync files</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div>
                <Label>Provider</Label>
                <Select
                  value={formData.provider}
                  onValueChange={(value) => setFormData({ ...formData, provider: value })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select provider" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="s3">Amazon S3</SelectItem>
                    <SelectItem value="gcs">Google Cloud Storage</SelectItem>
                    <SelectItem value="azure">Azure Storage</SelectItem>
                    <SelectItem value="dropbox">Dropbox</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Connection Name</Label>
                <Input
                  value={formData.connectionName}
                  onChange={(e) => setFormData({ ...formData, connectionName: e.target.value })}
                  placeholder="My Storage"
                />
              </div>
              <div>
                <Label>Bucket/Container</Label>
                <Input
                  value={formData.bucket}
                  onChange={(e) => setFormData({ ...formData, bucket: e.target.value })}
                  placeholder="my-bucket"
                />
              </div>
              <div>
                <Label>Region</Label>
                <Input
                  value={formData.region}
                  onChange={(e) => setFormData({ ...formData, region: e.target.value })}
                  placeholder="us-east-1"
                />
              </div>
              <div>
                <Label>Access Key</Label>
                <Input
                  value={formData.accessKey}
                  onChange={(e) => setFormData({ ...formData, accessKey: e.target.value })}
                  type="password"
                />
              </div>
              <div>
                <Label>Secret Key</Label>
                <Input
                  value={formData.secretKey}
                  onChange={(e) => setFormData({ ...formData, secretKey: e.target.value })}
                  type="password"
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleCreate}>Create Connection</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {connections.map((connection) => (
          <Card key={connection.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                  {getProviderIcon(connection.provider)}
                  <div>
                    <CardTitle className="text-lg">{connection.connectionName}</CardTitle>
                    <CardDescription className="capitalize">{connection.provider}</CardDescription>
                  </div>
                </div>
                <Badge variant={connection.syncEnabled ? "default" : "secondary"}>
                  {connection.syncEnabled ? "Active" : "Inactive"}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm">
                {connection.config?.bucket && (
                  <div>
                    <span className="text-gray-500">Bucket:</span>{" "}
                    <span className="font-medium">{connection.config.bucket}</span>
                  </div>
                )}
                {connection.lastSyncAt && (
                  <div>
                    <span className="text-gray-500">Last Sync:</span>{" "}
                    <span className="font-medium">{new Date(connection.lastSyncAt).toLocaleString()}</span>
                  </div>
                )}
              </div>
              <div className="flex gap-2 mt-4">
                <Button size="sm" variant="outline" className="flex-1">
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Sync Now
                </Button>
                <Button size="sm" variant="destructive" onClick={() => handleDelete(connection.id)}>
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {connections.length === 0 && (
        <div className="text-center p-12 border-2 border-dashed rounded-lg">
          <Cloud className="h-12 w-12 mx-auto mb-4 text-gray-400" />
          <h3 className="text-lg font-semibold mb-2">No Storage Connections</h3>
          <p className="text-gray-500 mb-4">Connect external storage to sync and manage files</p>
          <Button onClick={() => setDialogOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            Add First Connection
          </Button>
        </div>
      )}
    </div>
  );
}
