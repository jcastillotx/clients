"use client";

import React, { useState, useEffect, useCallback } from "react";
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
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Cloud, Plus, Trash2, RefreshCw, HardDrive, FolderOpen, Building2, Users, CheckCircle2, AlertTriangle, XCircle } from "lucide-react";
import { toast } from "sonner";

type Provider = "s3" | "dropbox" | "google-drive" | "onedrive" | "gcs" | "azure";
type OwnerType = "company" | "client";

interface StorageConnection {
  id: string;
  provider: Provider;
  ownerType: OwnerType;
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
  isAdmin: boolean;
  companyClientId?: string | null;
}

const PROVIDER_META: Record<Provider, { label: string; color: string; description: string }> = {
  s3: { label: "AWS S3", color: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200", description: "Amazon Simple Storage Service" },
  "google-drive": { label: "Google Drive", color: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200", description: "Google Workspace file storage" },
  dropbox: { label: "Dropbox", color: "bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200", description: "Dropbox cloud storage" },
  onedrive: { label: "OneDrive", color: "bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200", description: "Microsoft OneDrive / SharePoint" },
  gcs: { label: "Google Cloud Storage", color: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200", description: "Google Cloud Storage buckets" },
  azure: { label: "Azure Blob", color: "bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200", description: "Azure Blob Storage" },
};

const COMPANY_PROVIDERS: Provider[] = ["s3", "dropbox", "google-drive", "onedrive"];
const CLIENT_PROVIDERS: Provider[] = ["dropbox", "google-drive", "onedrive"];

const DEFAULT_FORM = {
  provider: "s3" as Provider,
  connectionName: "",
  bucket: "",
  region: "us-east-1",
  path: "",
  accessKeyId: "",
  secretAccessKey: "",
  refreshToken: "",
  accountEmail: "",
  appKey: "",
  appSecret: "",
  serviceAccountJson: "",
  projectId: "",
  autoSync: true,
  syncInterval: 60,
};

function ProviderCredentialFields({
  provider,
  formData,
  onChange,
}: {
  provider: Provider;
  formData: typeof DEFAULT_FORM;
  onChange: (patch: Partial<typeof DEFAULT_FORM>) => void;
}) {
  switch (provider) {
    case "s3":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">AWS S3 Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Open AWS Console &rarr; IAM &rarr; create or select a user with programmatic access.</li>
              <li>Attach a scoped S3 policy or <code>AmazonS3FullAccess</code>.</li>
              <li>Copy <strong>Access Key ID</strong> and <strong>Secret Access Key</strong>.</li>
              <li>Enter your bucket name and region below.</li>
            </ol>
          </div>
          <div>
            <Label>Bucket Name</Label>
            <Input value={formData.bucket} onChange={(e) => onChange({ bucket: e.target.value })} placeholder="my-company-bucket" />
          </div>
          <div>
            <Label>Region</Label>
            <Input value={formData.region} onChange={(e) => onChange({ region: e.target.value })} placeholder="us-east-1" />
          </div>
          <div>
            <Label>Access Key ID</Label>
            <Input value={formData.accessKeyId} onChange={(e) => onChange({ accessKeyId: e.target.value })} placeholder="AKIA..." />
          </div>
          <div>
            <Label>Secret Access Key</Label>
            <Input value={formData.secretAccessKey} onChange={(e) => onChange({ secretAccessKey: e.target.value })} type="password" />
          </div>
          <div>
            <Label>Base Path (optional)</Label>
            <Input value={formData.path} onChange={(e) => onChange({ path: e.target.value })} placeholder="uploads/" />
          </div>
        </>
      );

    case "google-drive":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">Google Drive Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Go to <strong>Google Cloud Console</strong> &rarr; APIs &amp; Services &rarr; Credentials.</li>
              <li>Create an OAuth 2.0 client or Service Account.</li>
              <li>Enable the <strong>Google Drive API</strong>.</li>
              <li>For OAuth: complete the consent flow and paste the refresh token below.</li>
              <li>For Service Account: paste the JSON key contents below.</li>
            </ol>
          </div>
          <div>
            <Label>Account Email</Label>
            <Input value={formData.accountEmail} onChange={(e) => onChange({ accountEmail: e.target.value })} placeholder="user@company.com or service-account@project.iam.gserviceaccount.com" />
          </div>
          <div>
            <Label>Refresh Token or Service Account JSON</Label>
            <Textarea
              value={formData.refreshToken || formData.serviceAccountJson}
              onChange={(e) => {
                const val = e.target.value;
                if (val.trim().startsWith("{")) {
                  onChange({ serviceAccountJson: val, refreshToken: "" });
                } else {
                  onChange({ refreshToken: val, serviceAccountJson: "" });
                }
              }}
              placeholder="Paste OAuth refresh token or full service account JSON key"
              rows={4}
            />
          </div>
          <div>
            <Label>Root Folder Path (optional)</Label>
            <Input value={formData.path} onChange={(e) => onChange({ path: e.target.value })} placeholder="My Drive/Projects" />
          </div>
        </>
      );

    case "dropbox":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">Dropbox Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Go to <strong>Dropbox App Console</strong> (dropbox.com/developers/apps).</li>
              <li>Create a new app with <strong>Full Dropbox</strong> or <strong>App Folder</strong> access.</li>
              <li>Under Permissions, enable <code>files.metadata.read</code>, <code>files.content.read</code>, and <code>files.content.write</code>.</li>
              <li>Generate an access token (or use OAuth to get a refresh token).</li>
              <li>Copy the App Key, App Secret, and refresh token below.</li>
            </ol>
          </div>
          <div>
            <Label>Account Email</Label>
            <Input value={formData.accountEmail} onChange={(e) => onChange({ accountEmail: e.target.value })} placeholder="user@company.com" />
          </div>
          <div>
            <Label>App Key</Label>
            <Input value={formData.appKey} onChange={(e) => onChange({ appKey: e.target.value })} placeholder="xxxxxxxxxxxxxxx" />
          </div>
          <div>
            <Label>App Secret</Label>
            <Input value={formData.appSecret} onChange={(e) => onChange({ appSecret: e.target.value })} type="password" />
          </div>
          <div>
            <Label>Refresh Token</Label>
            <Input value={formData.refreshToken} onChange={(e) => onChange({ refreshToken: e.target.value })} type="password" />
          </div>
          <div>
            <Label>Root Folder Path (optional)</Label>
            <Input value={formData.path} onChange={(e) => onChange({ path: e.target.value })} placeholder="/Company Files" />
          </div>
        </>
      );

    case "onedrive":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">OneDrive / SharePoint Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Go to <strong>Azure Portal</strong> &rarr; App Registrations &rarr; register a new app.</li>
              <li>Under API Permissions add <code>Files.ReadWrite.All</code> (delegated or application).</li>
              <li>Create a client secret under Certificates &amp; Secrets.</li>
              <li>Complete the OAuth consent flow to get a refresh token.</li>
              <li>Paste the App (Client) ID, Client Secret, and Refresh Token below.</li>
            </ol>
          </div>
          <div>
            <Label>Account Email</Label>
            <Input value={formData.accountEmail} onChange={(e) => onChange({ accountEmail: e.target.value })} placeholder="user@company.onmicrosoft.com" />
          </div>
          <div>
            <Label>Application (Client) ID</Label>
            <Input value={formData.appKey} onChange={(e) => onChange({ appKey: e.target.value })} placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
          </div>
          <div>
            <Label>Client Secret</Label>
            <Input value={formData.appSecret} onChange={(e) => onChange({ appSecret: e.target.value })} type="password" />
          </div>
          <div>
            <Label>Refresh Token</Label>
            <Input value={formData.refreshToken} onChange={(e) => onChange({ refreshToken: e.target.value })} type="password" />
          </div>
          <div>
            <Label>Root Folder Path (optional)</Label>
            <Input value={formData.path} onChange={(e) => onChange({ path: e.target.value })} placeholder="/Documents/Client Files" />
          </div>
        </>
      );

    case "gcs":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">Google Cloud Storage Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Open Google Cloud Console &rarr; IAM &amp; Admin &rarr; Service Accounts.</li>
              <li>Create a service account and grant Storage permissions.</li>
              <li>Create a JSON key and download it.</li>
              <li>Paste the project ID and full key file contents below.</li>
            </ol>
          </div>
          <div>
            <Label>Bucket Name</Label>
            <Input value={formData.bucket} onChange={(e) => onChange({ bucket: e.target.value })} placeholder="my-gcs-bucket" />
          </div>
          <div>
            <Label>GCP Project ID</Label>
            <Input value={formData.projectId} onChange={(e) => onChange({ projectId: e.target.value })} placeholder="my-gcp-project" />
          </div>
          <div>
            <Label>Service Account JSON</Label>
            <Textarea
              value={formData.serviceAccountJson}
              onChange={(e) => onChange({ serviceAccountJson: e.target.value })}
              placeholder='{"type":"service_account",...}'
              rows={4}
            />
          </div>
        </>
      );

    case "azure":
      return (
        <>
          <div className="rounded-md border border-border/70 bg-muted/30 p-3 text-sm">
            <p className="font-medium">Azure Blob Storage Setup</p>
            <ol className="mt-2 list-decimal space-y-1 pl-5 text-muted-foreground">
              <li>Open Azure Portal &rarr; Storage Accounts &rarr; select or create an account.</li>
              <li>Go to Access Keys and copy the connection string or account key.</li>
              <li>Enter the container name and key below.</li>
            </ol>
          </div>
          <div>
            <Label>Container Name</Label>
            <Input value={formData.bucket} onChange={(e) => onChange({ bucket: e.target.value })} placeholder="my-container" />
          </div>
          <div>
            <Label>Account Name</Label>
            <Input value={formData.accessKeyId} onChange={(e) => onChange({ accessKeyId: e.target.value })} placeholder="mystorageaccount" />
          </div>
          <div>
            <Label>Account Key</Label>
            <Input value={formData.secretAccessKey} onChange={(e) => onChange({ secretAccessKey: e.target.value })} type="password" />
          </div>
        </>
      );
  }
}

function buildCredentials(provider: Provider, formData: typeof DEFAULT_FORM): Record<string, string> {
  switch (provider) {
    case "s3":
      return { accessKeyId: formData.accessKeyId, secretAccessKey: formData.secretAccessKey };
    case "google-drive":
      return {
        accountEmail: formData.accountEmail,
        ...(formData.serviceAccountJson
          ? { serviceAccountJson: formData.serviceAccountJson }
          : { refreshToken: formData.refreshToken }),
      };
    case "dropbox":
      return {
        accountEmail: formData.accountEmail,
        appKey: formData.appKey,
        appSecret: formData.appSecret,
        refreshToken: formData.refreshToken,
      };
    case "onedrive":
      return {
        accountEmail: formData.accountEmail,
        clientId: formData.appKey,
        clientSecret: formData.appSecret,
        refreshToken: formData.refreshToken,
      };
    case "gcs":
      return { projectId: formData.projectId, serviceAccountJson: formData.serviceAccountJson };
    case "azure":
      return { accountName: formData.accessKeyId, accountKey: formData.secretAccessKey };
  }
}

function validateCredentials(provider: Provider, formData: typeof DEFAULT_FORM): string | null {
  if (!formData.connectionName.trim()) return "Connection name is required";

  switch (provider) {
    case "s3":
      if (!formData.bucket) return "Bucket name is required";
      if (!formData.accessKeyId) return "Access Key ID is required";
      if (!formData.secretAccessKey) return "Secret Access Key is required";
      break;
    case "google-drive":
      if (!formData.accountEmail) return "Account email is required";
      if (!formData.refreshToken && !formData.serviceAccountJson)
        return "Refresh token or service account JSON is required";
      break;
    case "dropbox":
      if (!formData.appKey) return "App Key is required";
      if (!formData.appSecret) return "App Secret is required";
      if (!formData.refreshToken) return "Refresh Token is required";
      break;
    case "onedrive":
      if (!formData.appKey) return "Application (Client) ID is required";
      if (!formData.appSecret) return "Client Secret is required";
      if (!formData.refreshToken) return "Refresh Token is required";
      break;
    case "gcs":
      if (!formData.bucket) return "Bucket name is required";
      if (!formData.projectId) return "Project ID is required";
      if (!formData.serviceAccountJson) return "Service Account JSON is required";
      break;
    case "azure":
      if (!formData.bucket) return "Container name is required";
      if (!formData.accessKeyId) return "Account name is required";
      if (!formData.secretAccessKey) return "Account key is required";
      break;
  }
  return null;
}

type ConnectionStatus = "healthy" | "warning" | "broken";

function getConnectionStatus(connection: StorageConnection): ConnectionStatus {
  if (!connection.syncEnabled) return "broken";
  if (!connection.lastSyncAt) return "warning"; // never synced
  const hoursSinceSync = (Date.now() - new Date(connection.lastSyncAt).getTime()) / 36e5;
  if (hoursSinceSync > 24) return "warning";
  return "healthy";
}

const STATUS_META: Record<ConnectionStatus, {
  label: string;
  icon: React.ReactNode;
  borderClass: string;
  badgeClass: string;
}> = {
  healthy: {
    label: "Healthy",
    icon: <CheckCircle2 className="h-4 w-4 text-green-500" />,
    borderClass: "border-l-4 border-l-green-500",
    badgeClass: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300",
  },
  warning: {
    label: "Issues",
    icon: <AlertTriangle className="h-4 w-4 text-yellow-500" />,
    borderClass: "border-l-4 border-l-yellow-500",
    badgeClass: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300",
  },
  broken: {
    label: "Broken",
    icon: <XCircle className="h-4 w-4 text-red-500" />,
    borderClass: "border-l-4 border-l-red-500",
    badgeClass: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
  },
};

function ConnectionCard({
  connection,
  onDelete,
}: {
  connection: StorageConnection;
  onDelete: (id: string) => void;
}) {
  const meta = PROVIDER_META[connection.provider] || PROVIDER_META.s3;
  const status = getConnectionStatus(connection);
  const statusMeta = STATUS_META[status];

  return (
    <Card className={statusMeta.borderClass}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-muted p-2">
              <Cloud className="h-5 w-5" />
            </div>
            <div>
              <CardTitle className="text-base">{connection.connectionName}</CardTitle>
              <CardDescription>{meta.description}</CardDescription>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge className={meta.color}>{meta.label}</Badge>
            <Badge className={statusMeta.badgeClass}>
              <span className="flex items-center gap-1">
                {statusMeta.icon}
                {statusMeta.label}
              </span>
            </Badge>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-1.5 text-sm">
          {connection.config?.bucket && (
            <div className="flex justify-between">
              <span className="text-muted-foreground">
                {connection.provider === "azure" ? "Container:" : connection.provider === "s3" || connection.provider === "gcs" ? "Bucket:" : "Root Path:"}
              </span>
              <span className="font-medium">{connection.config.bucket}</span>
            </div>
          )}
          {connection.config?.region && (
            <div className="flex justify-between">
              <span className="text-muted-foreground">Region:</span>
              <span className="font-medium">{connection.config.region}</span>
            </div>
          )}
          <div className="flex justify-between">
            <span className="text-muted-foreground">Last Sync:</span>
            <span className="font-medium">
              {connection.lastSyncAt
                ? new Date(connection.lastSyncAt).toLocaleString()
                : "Never"}
            </span>
          </div>
          {status === "warning" && connection.lastSyncAt && (
            <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
              Last sync was over 24 hours ago. Check your connection settings.
            </p>
          )}
          {status === "broken" && (
            <p className="text-xs text-red-600 dark:text-red-400 mt-1">
              Sync is disabled. Enable sync to restore this connection.
            </p>
          )}
        </div>
        <div className="flex gap-2 mt-4">
          <Button size="sm" variant="outline" className="flex-1" onClick={() => toast.info("Sync initiated")}>
            <RefreshCw className="h-4 w-4 mr-2" />
            Sync Now
          </Button>
          <Button size="sm" variant="destructive" onClick={() => onDelete(connection.id)}>
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function AddConnectionDialog({
  ownerType,
  providers,
  clientId,
  onCreated,
}: {
  ownerType: OwnerType;
  providers: Provider[];
  clientId: string;
  onCreated: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState({ ...DEFAULT_FORM, provider: providers[0] });
  const [saving, setSaving] = useState(false);

  const update = (patch: Partial<typeof DEFAULT_FORM>) => setFormData((prev) => ({ ...prev, ...patch }));

  const handleCreate = async () => {
    const error = validateCredentials(formData.provider, formData);
    if (error) {
      toast.error(error);
      return;
    }

    setSaving(true);
    try {
      const response = await fetch("/api/storage-connections", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          clientId,
          provider: formData.provider,
          ownerType,
          connectionName: formData.connectionName,
          credentials: buildCredentials(formData.provider, formData),
          config: {
            bucket: formData.bucket || undefined,
            region: formData.region || undefined,
            path: formData.path || undefined,
            autoSync: formData.autoSync,
            syncInterval: formData.syncInterval,
          },
        }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.error || "Failed to create connection");
      }

      toast.success("Storage connection created");
      setOpen(false);
      setFormData({ ...DEFAULT_FORM, provider: providers[0] });
      onCreated();
    } catch (err: any) {
      toast.error(err.message || "Failed to create connection");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button size="sm">
          <Plus className="h-4 w-4 mr-2" />
          Add Connection
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>
            {ownerType === "company" ? "New Company Storage Connection" : "New Client Storage Connection"}
          </DialogTitle>
          <DialogDescription>
            {ownerType === "company"
              ? "Connect a company-wide storage provider accessible to all staff"
              : "Connect this client's cloud storage so staff can access their files"}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div>
            <Label>Provider</Label>
            <Select
              value={formData.provider}
              onValueChange={(value) => update({ provider: value as Provider })}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select provider" />
              </SelectTrigger>
              <SelectContent>
                {providers.map((p) => (
                  <SelectItem key={p} value={p}>
                    {PROVIDER_META[p].label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div>
            <Label>Connection Name</Label>
            <Input
              value={formData.connectionName}
              onChange={(e) => update({ connectionName: e.target.value })}
              placeholder={ownerType === "company" ? "Company Primary Storage" : "Client Google Drive"}
            />
          </div>

          <ProviderCredentialFields provider={formData.provider} formData={formData} onChange={update} />

          <Separator />

          <div className="flex items-center justify-between">
            <div>
              <Label>Auto-Sync</Label>
              <p className="text-xs text-muted-foreground">Automatically sync files on a schedule</p>
            </div>
            <Switch checked={formData.autoSync} onCheckedChange={(checked) => update({ autoSync: checked })} />
          </div>
          {formData.autoSync && (
            <div>
              <Label>Sync Interval (minutes)</Label>
              <Input
                type="number"
                min={5}
                max={1440}
                value={formData.syncInterval}
                onChange={(e) => update({ syncInterval: parseInt(e.target.value) || 60 })}
              />
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancel
          </Button>
          <Button onClick={handleCreate} disabled={saving}>
            {saving ? "Creating..." : "Create Connection"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function StorageConnections({ clientId, isAdmin, companyClientId }: StorageConnectionsProps) {
  const [companyConnections, setCompanyConnections] = useState<StorageConnection[]>([]);
  const [clientConnections, setClientConnections] = useState<StorageConnection[]>([]);
  const [loading, setLoading] = useState(true);

  const resolvedCompanyClientId = companyClientId || clientId;

  const fetchConnections = useCallback(async () => {
    try {
      setLoading(true);

      const fetches: Promise<Response>[] = [];

      // Fetch company-level connections (admin sees these)
      if (isAdmin && resolvedCompanyClientId) {
        fetches.push(fetch(`/api/storage-connections?clientId=${resolvedCompanyClientId}&ownerType=company`));
      }

      // Fetch client-level connections
      fetches.push(fetch(`/api/storage-connections?clientId=${clientId}&ownerType=client`));

      const responses = await Promise.all(fetches);
      const results = await Promise.all(responses.map((r) => (r.ok ? r.json() : [])));

      if (isAdmin && resolvedCompanyClientId) {
        setCompanyConnections(results[0] || []);
        setClientConnections(results[1] || []);
      } else {
        setCompanyConnections([]);
        setClientConnections(results[0] || []);
      }
    } catch {
      toast.error("Failed to load storage connections");
    } finally {
      setLoading(false);
    }
  }, [clientId, isAdmin, resolvedCompanyClientId]);

  useEffect(() => {
    fetchConnections();
  }, [fetchConnections]);

  const handleDelete = async (id: string) => {
    if (!confirm("Are you sure you want to remove this storage connection?")) return;

    try {
      const response = await fetch(`/api/storage-connections?id=${id}`, { method: "DELETE" });
      if (!response.ok) throw new Error("Failed to delete connection");
      toast.success("Connection removed");
      fetchConnections();
    } catch {
      toast.error("Failed to delete connection");
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-12 text-muted-foreground">
        <RefreshCw className="h-5 w-5 mr-2 animate-spin" />
        Loading storage connections...
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* Company Storage Section */}
      {isAdmin && (
        <section>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="rounded-lg bg-primary/10 p-2">
                <Building2 className="h-5 w-5 text-primary" />
              </div>
              <div>
                <h2 className="text-xl font-semibold">Company Storage</h2>
                <p className="text-sm text-muted-foreground">
                  Company-wide storage connections accessible to all staff. AWS S3 is recommended as primary storage.
                </p>
              </div>
            </div>
            <AddConnectionDialog
              ownerType="company"
              providers={COMPANY_PROVIDERS}
              clientId={resolvedCompanyClientId}
              onCreated={fetchConnections}
            />
          </div>

          {companyConnections.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {companyConnections.map((conn) => (
                <ConnectionCard key={conn.id} connection={conn} onDelete={handleDelete} />
              ))}
            </div>
          ) : (
            <Card className="border-dashed">
              <CardContent className="flex flex-col items-center justify-center py-8 text-center">
                <HardDrive className="h-10 w-10 mb-3 text-muted-foreground/50" />
                <h3 className="font-semibold mb-1">No Company Storage Connected</h3>
                <p className="text-sm text-muted-foreground mb-4 max-w-md">
                  Set up your primary company storage (AWS S3 recommended) and optionally connect Dropbox, Google Drive, or OneDrive for company-wide file access.
                </p>
              </CardContent>
            </Card>
          )}
        </section>
      )}

      {isAdmin && <Separator />}

      {/* Client Storage Section */}
      <section>
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-primary/10 p-2">
              <Users className="h-5 w-5 text-primary" />
            </div>
            <div>
              <h2 className="text-xl font-semibold">Client Storage</h2>
              <p className="text-sm text-muted-foreground">
                Connect this client&apos;s own cloud storage so staff can access and manage their files directly.
              </p>
            </div>
          </div>
          <AddConnectionDialog
            ownerType="client"
            providers={CLIENT_PROVIDERS}
            clientId={clientId}
            onCreated={fetchConnections}
          />
        </div>

        {clientConnections.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {clientConnections.map((conn) => (
              <ConnectionCard key={conn.id} connection={conn} onDelete={handleDelete} />
            ))}
          </div>
        ) : (
          <Card className="border-dashed">
            <CardContent className="flex flex-col items-center justify-center py-8 text-center">
              <FolderOpen className="h-10 w-10 mb-3 text-muted-foreground/50" />
              <h3 className="font-semibold mb-1">No Client Storage Connected</h3>
              <p className="text-sm text-muted-foreground mb-4 max-w-md">
                Connect the client&apos;s Dropbox, Google Drive, or OneDrive so your team can access their files without leaving the platform.
              </p>
            </CardContent>
          </Card>
        )}
      </section>
    </div>
  );
}
