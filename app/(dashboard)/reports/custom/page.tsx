"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Save, Plus, Settings } from "lucide-react";
import Link from "next/link";
import { DashboardBuilder } from "@/components/reports/dashboard-builder";
import { WidgetLibrary } from "@/components/reports/widget-library";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";

export default function CustomDashboardPage() {
  const router = useRouter();
  const [saving, setSaving] = useState(false);
  const [widgetLibraryOpen, setWidgetLibraryOpen] = useState(false);
  const [dashboard, setDashboard] = useState({
    name: "My Custom Dashboard",
    layout: { type: "grid" as const, columns: 12, gap: 4 },
    widgets: [] as any[],
  });

  const handleAddWidget = (widget: any) => {
    setDashboard((prev) => ({
      ...prev,
      widgets: [...prev.widgets, widget],
    }));
    setWidgetLibraryOpen(false);
    toast.success("Widget added to dashboard");
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const response = await fetch("/api/dashboards", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dashboard),
      });

      if (!response.ok) {
        throw new Error("Failed to save dashboard");
      }

      toast.success("Dashboard saved successfully");
      router.push("/reports");
    } catch (error) {
      console.error("Error saving dashboard:", error);
      toast.error("Failed to save dashboard");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/reports">
            <Button variant="ghost" size="icon">
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Custom Dashboard</h1>
            <p className="text-muted-foreground">Build your personalized analytics dashboard</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Sheet open={widgetLibraryOpen} onOpenChange={setWidgetLibraryOpen}>
            <SheetTrigger asChild>
              <Button variant="outline">
                <Plus className="mr-2 h-4 w-4" />
                Add Widget
              </Button>
            </SheetTrigger>
            <SheetContent className="w-[400px] sm:w-[540px]">
              <SheetHeader>
                <SheetTitle>Widget Library</SheetTitle>
                <SheetDescription>Choose a widget to add to your dashboard</SheetDescription>
              </SheetHeader>
              <WidgetLibrary onSelectWidget={handleAddWidget} />
            </SheetContent>
          </Sheet>
          <Button onClick={handleSave} disabled={saving}>
            <Save className="mr-2 h-4 w-4" />
            {saving ? "Saving..." : "Save Dashboard"}
          </Button>
        </div>
      </div>

      <DashboardBuilder dashboard={dashboard} onChange={setDashboard} />
    </div>
  );
}
