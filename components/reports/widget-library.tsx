"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { BarChart3, TrendingUp, Calendar, Activity, List, PieChart, Plus } from "lucide-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Badge } from "@/components/ui/badge";

interface WidgetLibraryProps {
  onSelectWidget: (widget: any) => void;
}

const WIDGET_CATEGORIES = [
  {
    name: "Metrics",
    widgets: [
      {
        id: "total-revenue",
        type: "metric",
        title: "Total Revenue",
        icon: TrendingUp,
        config: {
          dataSource: "invoices",
          metric: "sum",
          field: "total",
        },
      },
      {
        id: "active-projects",
        type: "metric",
        title: "Active Projects",
        icon: Activity,
        config: {
          dataSource: "projects",
          metric: "count",
          filters: { status: "active" },
        },
      },
    ],
  },
  {
    name: "Charts",
    widgets: [
      {
        id: "revenue-trend",
        type: "chart",
        title: "Revenue Trend",
        icon: BarChart3,
        config: {
          dataSource: "invoices",
          chartType: "line",
          metric: "sum",
          groupBy: ["month"],
        },
      },
      {
        id: "project-status",
        type: "chart",
        title: "Projects by Status",
        icon: PieChart,
        config: {
          dataSource: "projects",
          chartType: "pie",
          metric: "count",
          groupBy: ["status"],
        },
      },
      {
        id: "request-volume",
        type: "chart",
        title: "Request Volume",
        icon: BarChart3,
        config: {
          dataSource: "requests",
          chartType: "bar",
          metric: "count",
          groupBy: ["week"],
        },
      },
    ],
  },
  {
    name: "Lists & Tables",
    widgets: [
      {
        id: "recent-invoices",
        type: "table",
        title: "Recent Invoices",
        icon: List,
        config: {
          dataSource: "invoices",
          limit: 10,
          orderBy: "created_at",
          order: "desc",
        },
      },
      {
        id: "upcoming-meetings",
        type: "list",
        title: "Upcoming Meetings",
        icon: Calendar,
        config: {
          dataSource: "meetings",
          filters: { status: "scheduled" },
          limit: 5,
        },
      },
    ],
  },
  {
    name: "Activity",
    widgets: [
      {
        id: "recent-activity",
        type: "activity",
        title: "Recent Activity",
        icon: Activity,
        config: {
          dataSource: "activity_logs",
          limit: 10,
        },
      },
      {
        id: "calendar-view",
        type: "calendar",
        title: "Calendar View",
        icon: Calendar,
        config: {
          dataSource: "meetings",
        },
      },
    ],
  },
];

export function WidgetLibrary({ onSelectWidget }: WidgetLibraryProps) {
  const handleAddWidget = (widget: any) => {
    const newWidget = {
      ...widget,
      id: `${widget.id}-${Date.now()}`,
      position: { x: 0, y: 0, w: 4, h: 4 },
    };
    onSelectWidget(newWidget);
  };

  return (
    <ScrollArea className="h-[600px] pr-4">
      <div className="space-y-6 py-4">
        {WIDGET_CATEGORIES.map((category) => (
          <div key={category.name} className="space-y-3">
            <div className="flex items-center gap-2">
              <h3 className="font-semibold">{category.name}</h3>
              <Badge variant="secondary">{category.widgets.length}</Badge>
            </div>
            <div className="grid gap-3">
              {category.widgets.map((widget) => {
                const Icon = widget.icon;
                return (
                  <Card key={widget.id} className="hover:border-primary transition-colors">
                    <CardHeader className="pb-3">
                      <div className="flex items-start justify-between">
                        <div className="flex items-center gap-2">
                          <Icon className="h-4 w-4 text-muted-foreground" />
                          <CardTitle className="text-sm">{widget.title}</CardTitle>
                        </div>
                        <Button size="sm" variant="ghost" onClick={() => handleAddWidget(widget)}>
                          <Plus className="h-4 w-4" />
                        </Button>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <CardDescription className="text-xs">
                        {widget.type === "metric" && "Display a single key metric"}
                        {widget.type === "chart" && `${widget.config.chartType} chart visualization`}
                        {widget.type === "table" && "Tabular data display"}
                        {widget.type === "list" && "List of items"}
                        {widget.type === "activity" && "Activity feed"}
                        {widget.type === "calendar" && "Calendar view"}
                      </CardDescription>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </ScrollArea>
  );
}
