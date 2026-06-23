"use client";

import { useState, useEffect, useCallback } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Play, Square, Clock } from "lucide-react";
import { toast } from "sonner";
import { fetchApi } from "@/lib/api/client";

interface TimeTrackerProps {
  onTimerStop?: () => void;
}

export function TimeTracker({ onTimerStop }: TimeTrackerProps) {
  const [isRunning, setIsRunning] = useState(false);
  const [elapsedTime, setElapsedTime] = useState(0);
  const [startTime, setStartTime] = useState<Date | null>(null);
  const [currentEntry, setCurrentEntry] = useState<any>(null);

  // Form state
  const [description, setDescription] = useState("");
  const [clientId, setClientId] = useState("");
  const [requestId, setRequestId] = useState("");
  const [isBillable, setIsBillable] = useState(true);
  const [hourlyRate, setHourlyRate] = useState("");

  // Load running timer on mount
  useEffect(() => {
    checkRunningTimer();
  }, []);

  // Update elapsed time every second when running
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isRunning && startTime) {
      interval = setInterval(() => {
        const now = new Date().getTime();
        const start = startTime.getTime();
        setElapsedTime(Math.floor((now - start) / 1000));
      }, 1000);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [isRunning, startTime]);

  const checkRunningTimer = async () => {
    try {
      const payload = await fetchApi<{
        running?: boolean;
        entry?: {
          startedAt: string;
          description?: string;
          clientId?: string;
          requestId?: string;
          isBillable: boolean;
          hourlyRate?: string;
        };
      }>("/api/time-tracking/start", undefined, { fallbackMessage: "Failed to check timer" });

      if (payload.running && payload.entry) {
        setIsRunning(true);
        setCurrentEntry(payload.entry);
        setStartTime(new Date(payload.entry.startedAt));
        setDescription(payload.entry.description || "");
        setClientId(payload.entry.clientId || "");
        setRequestId(payload.entry.requestId || "");
        setIsBillable(payload.entry.isBillable);
        setHourlyRate(payload.entry.hourlyRate || "");
      }
    } catch (error) {
      console.error("Error checking running timer:", error);
    }
  };

  const startTimer = async () => {
    try {
      const entry = await fetchApi<{ startedAt: string }>(
        "/api/time-tracking/start",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            description,
            clientId: clientId || null,
            requestId: requestId || null,
            isBillable,
            hourlyRate: hourlyRate ? parseFloat(hourlyRate) : null,
          }),
        },
        { fallbackMessage: "Failed to start timer" },
      );
      setIsRunning(true);
      setCurrentEntry(entry);
      setStartTime(new Date(entry.startedAt));
      setElapsedTime(0);
      toast.success("Timer started");
    } catch (error: any) {
      toast.error(error.message || "Failed to start timer");
    }
  };

  const stopTimer = async () => {
    try {
      const stoppedEntry = await fetchApi<{ durationMinutes: number }>(
        "/api/time-tracking/stop",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id: currentEntry?.id,
          }),
        },
        { fallbackMessage: "Failed to stop timer" },
      );
      setIsRunning(false);
      setCurrentEntry(null);
      setStartTime(null);
      setElapsedTime(0);

      // Reset form
      setDescription("");
      setClientId("");
      setRequestId("");
      setIsBillable(true);
      setHourlyRate("");

      toast.success(`Timer stopped. Duration: ${formatDuration(stoppedEntry.durationMinutes * 60)}`);

      if (onTimerStop) {
        onTimerStop();
      }
    } catch (error) {
      toast.error("Failed to stop timer");
    }
  };

  const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Clock className="h-5 w-5" />
          Time Tracker
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Timer Display */}
        <div className="text-center py-6">
          <div className="text-5xl font-mono font-bold text-primary">{formatDuration(elapsedTime)}</div>
          {isRunning && startTime && (
            <p className="text-sm text-muted-foreground mt-2">Started at {startTime.toLocaleTimeString()}</p>
          )}
        </div>

        {/* Timer Controls */}
        <div className="flex gap-2 justify-center">
          {!isRunning ? (
            <Button onClick={startTimer} size="lg" className="gap-2">
              <Play className="h-5 w-5" />
              Start Timer
            </Button>
          ) : (
            <Button onClick={stopTimer} size="lg" variant="destructive" className="gap-2">
              <Square className="h-5 w-5" />
              Stop Timer
            </Button>
          )}
        </div>

        {/* Timer Settings */}
        <div className="space-y-4 pt-4 border-t">
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="What are you working on?"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              disabled={isRunning}
              rows={2}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="clientId">Client (Optional)</Label>
              <Input
                id="clientId"
                placeholder="Client ID"
                value={clientId}
                onChange={(e) => setClientId(e.target.value)}
                disabled={isRunning}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="requestId">Request (Optional)</Label>
              <Input
                id="requestId"
                placeholder="Request ID"
                value={requestId}
                onChange={(e) => setRequestId(e.target.value)}
                disabled={isRunning}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center space-x-2">
              <Switch id="billable" checked={isBillable} onCheckedChange={setIsBillable} disabled={isRunning} />
              <Label htmlFor="billable">Billable</Label>
            </div>

            <div className="space-y-2">
              <Label htmlFor="hourlyRate">Hourly Rate ($)</Label>
              <Input
                id="hourlyRate"
                type="number"
                step="0.01"
                placeholder="0.00"
                value={hourlyRate}
                onChange={(e) => setHourlyRate(e.target.value)}
                disabled={isRunning}
              />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
