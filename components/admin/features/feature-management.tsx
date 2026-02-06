"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";

interface Feature {
  id: string;
  name: string;
  display_name: string;
  description: string | null;
  category: string;
  is_enabled_by_default: boolean;
  requires_setup: boolean;
  dependencies: string[] | null;
}

interface Client {
  id: string;
  company_name: string;
}

interface Role {
  id: string;
  name: string;
  description: string | null;
}

interface Props {
  features: Feature[];
  clients: Client[];
  roles: Role[];
}

export function FeatureManagement({ features, clients, roles }: Props) {
  const [selectedClient, setSelectedClient] = useState<string>("");
  const [selectedRole, setSelectedRole] = useState<string>("");

  // Group features by category
  const featuresByCategory = features.reduce(
    (acc, feature) => {
      if (!acc[feature.category]) {
        acc[feature.category] = [];
      }
      acc[feature.category].push(feature);
      return acc;
    },
    {} as Record<string, Feature[]>,
  );

  const categoryLabels: Record<string, string> = {
    core: "Core Features",
    finance: "Finance & Billing",
    operations: "Operations",
    projects: "Project Management",
    communication: "Communication",
    marketing: "Marketing",
    brand: "Brand Management",
    ai: "AI & Automation",
    automation: "Automation",
    reporting: "Reports & Analytics",
    partnerships: "Partners & Referrals",
    knowledge: "Knowledge Base",
    feedback: "Surveys & Feedback",
    analytics: "Analytics",
    files: "File Management",
    integrations: "Integrations",
    security: "Security & Privacy",
    system: "System Configuration",
    support: "Support",
    sales: "Sales",
    services: "Services",
    legal: "Legal",
  };

  const handleToggleGlobal = async (featureId: string, isEnabled: boolean) => {
    const response = await fetch("/api/admin/features/global", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ featureId, isEnabled }),
    });

    if (!response.ok) {
      console.error("Failed to update feature");
    }
  };

  const handleToggleClient = async (featureId: string, isEnabled: boolean) => {
    if (!selectedClient) return;

    const response = await fetch("/api/admin/features/client", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ featureId, clientId: selectedClient, isEnabled }),
    });

    if (!response.ok) {
      console.error("Failed to update client feature");
    }
  };

  const handleToggleRole = async (featureId: string, isEnabled: boolean) => {
    if (!selectedRole) return;

    const response = await fetch("/api/admin/features/role", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ featureId, roleId: selectedRole, isEnabled }),
    });

    if (!response.ok) {
      console.error("Failed to update role feature");
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Feature Flags</CardTitle>
        <CardDescription>Enable or disable features at global, client, role, or user level</CardDescription>
      </CardHeader>
      <CardContent>
        <Tabs defaultValue="global" className="w-full">
          <TabsList>
            <TabsTrigger value="global">Global Defaults</TabsTrigger>
            <TabsTrigger value="client">By Client</TabsTrigger>
            <TabsTrigger value="role">By Role</TabsTrigger>
          </TabsList>

          <TabsContent value="global" className="space-y-6 mt-6">
            {Object.entries(featuresByCategory).map(([category, categoryFeatures]) => (
              <div key={category} className="space-y-4">
                <div>
                  <h3 className="text-lg font-semibold">{categoryLabels[category] || category}</h3>
                  <Separator className="mt-2" />
                </div>
                <div className="space-y-3">
                  {categoryFeatures.map((feature) => (
                    <div key={feature.id} className="flex items-center justify-between p-4 rounded-lg border">
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <h4 className="font-medium">{feature.display_name}</h4>
                          {feature.requires_setup && (
                            <Badge variant="outline" className="text-xs">
                              Requires Setup
                            </Badge>
                          )}
                        </div>
                        {feature.description && (
                          <p className="text-sm text-muted-foreground mt-1">{feature.description}</p>
                        )}
                        {feature.dependencies && feature.dependencies.length > 0 && (
                          <p className="text-xs text-muted-foreground mt-1">
                            Dependencies: {feature.dependencies.join(", ")}
                          </p>
                        )}
                      </div>
                      <Switch
                        checked={feature.is_enabled_by_default}
                        onCheckedChange={(checked) => handleToggleGlobal(feature.id, checked)}
                      />
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </TabsContent>

          <TabsContent value="client" className="space-y-6 mt-6">
            <div>
              <label className="text-sm font-medium">Select Client</label>
              <Select value={selectedClient} onValueChange={setSelectedClient}>
                <SelectTrigger className="w-full mt-2">
                  <SelectValue placeholder="Choose a client" />
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

            {selectedClient && (
              <div className="space-y-6">
                {Object.entries(featuresByCategory).map(([category, categoryFeatures]) => (
                  <div key={category} className="space-y-4">
                    <div>
                      <h3 className="text-lg font-semibold">{categoryLabels[category] || category}</h3>
                      <Separator className="mt-2" />
                    </div>
                    <div className="space-y-3">
                      {categoryFeatures.map((feature) => (
                        <div key={feature.id} className="flex items-center justify-between p-4 rounded-lg border">
                          <div className="flex-1">
                            <h4 className="font-medium">{feature.display_name}</h4>
                            {feature.description && (
                              <p className="text-sm text-muted-foreground mt-1">{feature.description}</p>
                            )}
                          </div>
                          <Switch
                            defaultChecked={feature.is_enabled_by_default}
                            onCheckedChange={(checked) => handleToggleClient(feature.id, checked)}
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </TabsContent>

          <TabsContent value="role" className="space-y-6 mt-6">
            <div>
              <label className="text-sm font-medium">Select Role</label>
              <Select value={selectedRole} onValueChange={setSelectedRole}>
                <SelectTrigger className="w-full mt-2">
                  <SelectValue placeholder="Choose a role" />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => (
                    <SelectItem key={role.id} value={role.id}>
                      {role.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {selectedRole && (
              <div className="space-y-6">
                {Object.entries(featuresByCategory).map(([category, categoryFeatures]) => (
                  <div key={category} className="space-y-4">
                    <div>
                      <h3 className="text-lg font-semibold">{categoryLabels[category] || category}</h3>
                      <Separator className="mt-2" />
                    </div>
                    <div className="space-y-3">
                      {categoryFeatures.map((feature) => (
                        <div key={feature.id} className="flex items-center justify-between p-4 rounded-lg border">
                          <div className="flex-1">
                            <h4 className="font-medium">{feature.display_name}</h4>
                            {feature.description && (
                              <p className="text-sm text-muted-foreground mt-1">{feature.description}</p>
                            )}
                          </div>
                          <Switch
                            defaultChecked={feature.is_enabled_by_default}
                            onCheckedChange={(checked) => handleToggleRole(feature.id, checked)}
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
}
