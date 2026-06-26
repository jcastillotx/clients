"use client";

import { useState } from "react";
import { useSearchParams } from "next/navigation";
import { TimeTracker } from "@/components/time-tracking/time-tracker";
import { TimeEntryList } from "@/components/time-tracking/time-entry-list";
import { TimeEntryForm } from "@/components/time-tracking/time-entry-form";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Clock, List, Plus } from "lucide-react";

export default function TimeTrackingPage() {
  const searchParams = useSearchParams();
  const projectId = searchParams.get("projectId");
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  const handleTimerStop = () => {
    setRefreshTrigger((prev) => prev + 1);
  };

  const handleEntryCreated = () => {
    setRefreshTrigger((prev) => prev + 1);
  };

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div>
        <h1 className="text-3xl font-bold">Time Tracking</h1>
        <p className="text-muted-foreground">Track your time, manage entries, and generate reports</p>
      </div>

      <Tabs defaultValue="timer" className="space-y-6">
        <TabsList>
          <TabsTrigger value="timer" className="gap-2">
            <Clock className="h-4 w-4" />
            Timer
          </TabsTrigger>
          <TabsTrigger value="entries" className="gap-2">
            <List className="h-4 w-4" />
            Time Entries
          </TabsTrigger>
          <TabsTrigger value="manual" className="gap-2">
            <Plus className="h-4 w-4" />
            Manual Entry
          </TabsTrigger>
        </TabsList>

        <TabsContent value="timer" className="space-y-6">
          <TimeTracker onTimerStop={handleTimerStop} initialProjectId={projectId} />
          <TimeEntryList refreshTrigger={refreshTrigger} projectId={projectId} />
        </TabsContent>

        <TabsContent value="entries">
          <TimeEntryList refreshTrigger={refreshTrigger} projectId={projectId} />
        </TabsContent>

        <TabsContent value="manual" className="space-y-6">
          <TimeEntryForm onEntryCreated={handleEntryCreated} initialProjectId={projectId} />
          <TimeEntryList refreshTrigger={refreshTrigger} projectId={projectId} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
