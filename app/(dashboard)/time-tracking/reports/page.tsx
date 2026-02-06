"use client";

import { TimeReport } from "@/components/time-tracking/time-report";
import { Button } from "@/components/ui/button";
import { ArrowLeft } from "lucide-react";
import Link from "next/link";

export default function TimeTrackingReportsPage() {
  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Time Tracking Reports</h1>
          <p className="text-muted-foreground">Generate detailed reports of your time entries</p>
        </div>
        <Link href="/time-tracking">
          <Button variant="outline" className="gap-2">
            <ArrowLeft className="h-4 w-4" />
            Back to Time Tracking
          </Button>
        </Link>
      </div>

      <TimeReport />
    </div>
  );
}
