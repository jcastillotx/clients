"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ArrowLeft, Save } from "lucide-react";
import Link from "next/link";
import { RuleBuilder } from "@/components/automation/rule-builder";
import { useRouter } from "next/navigation";
import { toast } from "sonner";

export default function AutomationBuilderPage() {
  const router = useRouter();
  const [saving, setSaving] = useState(false);
  const [rule, setRule] = useState({
    name: "",
    description: "",
    trigger: "",
    conditions: { operator: "and" as const, rules: [] },
    actions: [],
    isActive: true,
  });

  const handleSave = async () => {
    if (!rule.name || !rule.trigger || rule.actions.length === 0) {
      toast.error("Please complete all required fields");
      return;
    }

    setSaving(true);
    try {
      const response = await fetch("/api/automation/rules", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(rule),
      });

      if (!response.ok) {
        throw new Error("Failed to create automation rule");
      }

      toast.success("Automation rule created successfully");
      router.push("/automation");
    } catch (error) {
      console.error("Error saving automation rule:", error);
      toast.error("Failed to save automation rule");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="container mx-auto py-8 space-y-8 max-w-5xl">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/automation">
            <Button variant="ghost" size="icon">
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Automation Builder</h1>
            <p className="text-muted-foreground">Create a new workflow automation rule</p>
          </div>
        </div>
        <Button onClick={handleSave} disabled={saving}>
          <Save className="mr-2 h-4 w-4" />
          {saving ? "Saving..." : "Save Rule"}
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Rule Details</CardTitle>
          <CardDescription>Configure the basic information for your automation rule</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Rule Name *</Label>
            <Input
              id="name"
              placeholder="e.g., Send welcome email on new request"
              value={rule.name}
              onChange={(e) => setRule({ ...rule, name: e.target.value })}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="Describe what this automation does..."
              value={rule.description}
              onChange={(e) => setRule({ ...rule, description: e.target.value })}
            />
          </div>
        </CardContent>
      </Card>

      <RuleBuilder rule={rule} onChange={setRule} />
    </div>
  );
}
