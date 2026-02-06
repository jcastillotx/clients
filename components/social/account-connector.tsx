"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Facebook, Instagram, Twitter, Linkedin, Plus } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface AccountConnectorProps {
  clientId: string;
  onAccountConnected: (account: any) => void;
}

const platforms = [
  { value: "facebook", label: "Facebook", icon: Facebook },
  { value: "instagram", label: "Instagram", icon: Instagram },
  { value: "twitter", label: "Twitter", icon: Twitter },
  { value: "linkedin", label: "LinkedIn", icon: Linkedin },
];

export function AccountConnector({ clientId, onAccountConnected }: AccountConnectorProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [platform, setPlatform] = useState("");
  const { toast } = useToast();

  const handleConnect = async (selectedPlatform: string) => {
    setLoading(true);

    // TODO: Implement OAuth flow for each platform
    // For now, this is a placeholder that simulates the connection
    try {
      // In production, this would:
      // 1. Open OAuth popup for the platform
      // 2. Handle callback with access token
      // 3. Call API to store encrypted tokens

      toast({
        title: "OAuth Flow Required",
        description: `Please implement OAuth flow for ${selectedPlatform}. This would open a popup for user authorization.`,
        variant: "default",
      });

      // Simulated account for demonstration
      const mockAccount = {
        id: `mock-${Date.now()}`,
        clientId,
        platform: selectedPlatform,
        accountName: `${selectedPlatform} Account`,
        accountId: `${selectedPlatform}-${Date.now()}`,
        is_active: true,
        metadata: {
          profileImage: "",
          followerCount: 0,
        },
        created_at: new Date().toISOString(),
      };

      // In production, call the API:
      // const response = await fetch('/api/social/accounts', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify(accountData)
      // });
      // const newAccount = await response.json();

      onAccountConnected(mockAccount);
      setOpen(false);
      setPlatform("");
    } catch (error) {
      console.error("Error connecting account:", error);
      toast({
        title: "Error",
        description: "Failed to connect account. Please try again.",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          Connect Account
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Connect Social Media Account</DialogTitle>
          <DialogDescription>Choose a platform to connect your social media account</DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Platform</Label>
            <Select value={platform} onValueChange={setPlatform}>
              <SelectTrigger>
                <SelectValue placeholder="Select a platform" />
              </SelectTrigger>
              <SelectContent>
                {platforms.map((p) => {
                  const Icon = p.icon;
                  return (
                    <SelectItem key={p.value} value={p.value}>
                      <div className="flex items-center gap-2">
                        <Icon className="h-4 w-4" />
                        {p.label}
                      </div>
                    </SelectItem>
                  );
                })}
              </SelectContent>
            </Select>
          </div>

          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => handleConnect(platform)} disabled={!platform || loading}>
              {loading ? "Connecting..." : "Connect"}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
