"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import {
  addMonths,
  eachDayOfInterval,
  endOfMonth,
  format,
  isSameMonth,
  isToday,
  startOfMonth,
  subMonths,
} from "date-fns";
import {
  CalendarDays,
  ChevronLeft,
  ChevronRight,
  Megaphone,
  LifeBuoy,
  ClipboardCheck,
  Briefcase,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export type OperationsCalendarItemType = "meeting" | "project" | "request" | "ticket" | "campaign";

interface OperationsCalendarItem {
  id: string;
  title: string;
  date: string;
  href: string;
  type: OperationsCalendarItemType;
  subtitle?: string | null;
}

interface OperationsCalendarProps {
  items: OperationsCalendarItem[];
}

const typeConfig: Record<OperationsCalendarItemType, { label: string; dot: string; badge: string; icon: typeof CalendarDays }> = {
  meeting: {
    label: "Meetings",
    dot: "bg-blue-500",
    badge: "border-blue-200 bg-blue-50 text-blue-700",
    icon: CalendarDays,
  },
  project: {
    label: "Project Due",
    dot: "bg-violet-500",
    badge: "border-violet-200 bg-violet-50 text-violet-700",
    icon: Briefcase,
  },
  request: {
    label: "Requests",
    dot: "bg-amber-500",
    badge: "border-amber-200 bg-amber-50 text-amber-700",
    icon: ClipboardCheck,
  },
  ticket: {
    label: "Support",
    dot: "bg-rose-500",
    badge: "border-rose-200 bg-rose-50 text-rose-700",
    icon: LifeBuoy,
  },
  campaign: {
    label: "Campaigns",
    dot: "bg-emerald-500",
    badge: "border-emerald-200 bg-emerald-50 text-emerald-700",
    icon: Megaphone,
  },
};

export function OperationsCalendar({ items }: OperationsCalendarProps) {
  const [currentMonth, setCurrentMonth] = useState(new Date());

  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(currentMonth);
  const daysInMonth = eachDayOfInterval({ start: monthStart, end: monthEnd });
  const startingDayOfWeek = monthStart.getDay();

  const itemsByDate = useMemo(() => {
    const grouped = new Map<string, OperationsCalendarItem[]>();

    items.forEach((item) => {
      const parsedDate = new Date(item.date);
      if (Number.isNaN(parsedDate.getTime())) {
        return;
      }
      const dateKey = format(parsedDate, "yyyy-MM-dd");
      if (!grouped.has(dateKey)) {
        grouped.set(dateKey, []);
      }
      grouped.get(dateKey)?.push(item);
    });

    grouped.forEach((value, key) => {
      grouped.set(
        key,
        value.sort((a, b) => {
          const typeCompare = a.type.localeCompare(b.type);
          if (typeCompare !== 0) return typeCompare;
          return a.title.localeCompare(b.title);
        }),
      );
    });

    return grouped;
  }, [items]);

  return (
    <Card className="bg-gradient-to-br from-card to-secondary/20">
      <CardHeader>
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <CardTitle className="flex items-center gap-2">
              <CalendarDays className="h-5 w-5" />
              Operations Calendar
            </CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              Meetings, project due dates, requests, support tickets, and campaigns.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={() => setCurrentMonth(new Date())}>
              Today
            </Button>
            <Button variant="outline" size="icon" onClick={() => setCurrentMonth((prev) => subMonths(prev, 1))}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" onClick={() => setCurrentMonth((prev) => addMonths(prev, 1))}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
        <div className="flex flex-wrap gap-2 pt-2 text-xs">
          {Object.entries(typeConfig).map(([type, config]) => (
            <span key={type} className={cn("inline-flex items-center gap-2 rounded-full border px-2.5 py-1", config.badge)}>
              <span className={cn("h-2 w-2 rounded-full", config.dot)} />
              {config.label}
            </span>
          ))}
        </div>
      </CardHeader>
      <CardContent>
        <div className="mb-4 text-lg font-semibold">{format(currentMonth, "MMMM yyyy")}</div>
        <div className="grid grid-cols-7 gap-2">
          {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => (
            <div key={day} className="px-2 py-1 text-center text-sm font-semibold text-muted-foreground">
              {day}
            </div>
          ))}

          {Array.from({ length: startingDayOfWeek }).map((_, index) => (
            <div key={`empty-${index}`} className="min-h-[128px] rounded-md border bg-muted/20 p-2" />
          ))}

          {daysInMonth.map((day) => {
            const dateKey = format(day, "yyyy-MM-dd");
            const dayItems = itemsByDate.get(dateKey) || [];

            return (
              <div
                key={dateKey}
                className={cn(
                  "min-h-[128px] rounded-md border p-2 transition-colors",
                  isToday(day) && "border-primary bg-primary/5",
                  !isSameMonth(day, currentMonth) && "bg-muted/20 text-muted-foreground",
                )}
              >
                <div className={cn("mb-2 text-sm font-semibold", isToday(day) && "text-primary")}>{format(day, "d")}</div>
                <div className="space-y-1.5">
                  {dayItems.slice(0, 3).map((item) => {
                    const config = typeConfig[item.type];
                    const Icon = config.icon;
                    return (
                      <Link key={item.id} href={item.href} className="block">
                        <div className={cn("rounded-md border px-2 py-1.5 text-xs transition hover:opacity-90", config.badge)}>
                          <div className="flex items-center gap-1.5">
                            <Icon className="h-3 w-3" />
                            <span className="truncate font-medium">{item.title}</span>
                          </div>
                          {item.subtitle ? <div className="mt-0.5 truncate text-[11px] opacity-80">{item.subtitle}</div> : null}
                        </div>
                      </Link>
                    );
                  })}
                  {dayItems.length > 3 ? <div className="text-center text-xs text-muted-foreground">+{dayItems.length - 3} more</div> : null}
                </div>
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
