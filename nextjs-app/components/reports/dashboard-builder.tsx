"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Trash2, GripVertical, Settings } from "lucide-react";
import { cn } from "@/lib/utils";

interface DashboardBuilderProps {
  dashboard: any;
  onChange: (dashboard: any) => void;
}

export function DashboardBuilder({ dashboard, onChange }: DashboardBuilderProps) {
  const removeWidget = (widgetId: string) => {
    onChange({
      ...dashboard,
      widgets: dashboard.widgets.filter((w: any) => w.id !== widgetId),
    });
  };

  return (
    <div className="space-y-4">
      {dashboard.widgets.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-16">
            <div className="text-center space-y-2">
              <p className="text-lg font-medium">No widgets added yet</p>
              <p className="text-sm text-muted-foreground">
                Click "Add Widget" to start building your custom dashboard
              </p>
            </div>
          </CardContent>
        </Card>
      ) : (
        <div
          className={cn(
            "grid gap-4",
            dashboard.layout.type === "grid" && `grid-cols-${dashboard.layout.columns || 12}`,
          )}
          style={{
            gap: `${dashboard.layout.gap || 4 * 4}px`,
          }}
        >
          {dashboard.widgets.map((widget: any) => (
            <Card
              key={widget.id}
              className="relative group hover:border-primary transition-colors"
              style={{
                gridColumn: `span ${widget.position?.w || 4}`,
                gridRow: `span ${widget.position?.h || 4}`,
              }}
            >
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    <GripVertical className="h-4 w-4 text-muted-foreground cursor-move" />
                    <CardTitle className="text-sm">{widget.title}</CardTitle>
                  </div>
                  <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Button size="sm" variant="ghost" className="h-8 w-8 p-0">
                      <Settings className="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="ghost" className="h-8 w-8 p-0" onClick={() => removeWidget(widget.id)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
                <Badge variant="outline" className="w-fit">
                  {widget.type}
                </Badge>
              </CardHeader>
              <CardContent>
                <div className="rounded-md border-2 border-dashed border-muted-foreground/25 p-8">
                  <div className="text-center text-sm text-muted-foreground">
                    {widget.type === "metric" && "Metric widget preview"}
                    {widget.type === "chart" && `${widget.config.chartType || "Chart"} preview`}
                    {widget.type === "table" && "Table widget preview"}
                    {widget.type === "list" && "List widget preview"}
                    {widget.type === "activity" && "Activity feed preview"}
                    {widget.type === "calendar" && "Calendar widget preview"}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
