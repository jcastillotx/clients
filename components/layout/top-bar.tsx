"use client";

import { useState, useEffect } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Play, Pause, Square, Clock, Activity, CheckCircle2, AlertCircle, Megaphone } from "lucide-react";
import { toast } from "sonner";
import { createClient } from "@/lib/supabase/client";

interface TopBarProps {
  userRole: "admin" | "staff" | "client";
  userName: string;
  userEmail: string;
  clientId?: string;
}

interface ActiveTimer {
  startedAt: Date;
  description: string;
  clientId?: string;
  requestId?: string;
}

export function TopBar({ userRole, userName, userEmail, clientId }: TopBarProps) {
  // If client role, show news ticker instead
  if (userRole === "client") {
    return <ClientNewsTicker clientId={clientId} />;
  }

  // Admin and Staff get the full top bar
  return <StaffTopBar userRole={userRole} userName={userName} userEmail={userEmail} />;
}

/**
 * News Ticker for Clients
 */
function ClientNewsTicker({ clientId }: { clientId?: string }) {
  const [newsItems, setNewsItems] = useState<Array<{ id: string; title: string; content: string; created_at: string }>>([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const supabase = createClient();

  useEffect(() => {
    async function fetchNews() {
      // Fetch announcements/news for the client
      const { data } = await supabase
        .from("announcements")
        .select("*")
        .or(`client_id.eq.${clientId},client_id.is.null`) // Client-specific or global news
        .eq("is_active", true)
        .order("created_at", { ascending: false })
        .limit(10);

      if (data && data.length > 0) {
        setNewsItems(data);
      } else {
        // Default news if no announcements
        setNewsItems([
          { id: "1", title: "Welcome!", content: "Welcome to your client dashboard", created_at: new Date().toISOString() },
          { id: "2", title: "Need Help?", content: "Click 'Support' to create a ticket for assistance", created_at: new Date().toISOString() },
          { id: "3", title: "View Invoices", content: "Check your invoices and payment history anytime", created_at: new Date().toISOString() },
        ]);
      }
    }
    fetchNews();
  }, [clientId]);

  // Auto-rotate news items every 5 seconds
  useEffect(() => {
    if (newsItems.length <= 1) return;

    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % newsItems.length);
    }, 5000);

    return () => clearInterval(interval);
  }, [newsItems.length]);

  const currentNews = newsItems[currentIndex];

  return (
    <div className="sticky top-0 z-50 border-b bg-gradient-to-r from-primary/10 via-background to-primary/10 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="flex h-14 items-center px-4 md:px-6">
        <div className="flex items-center gap-3 flex-1 overflow-hidden">
          <Megaphone className="h-5 w-5 text-primary flex-shrink-0 animate-pulse" />
          <div className="flex-1 overflow-hidden">
            {currentNews && (
              <div className="animate-in fade-in slide-in-from-right-2 duration-500">
                <span className="font-semibold text-primary">{currentNews.title}:</span>{" "}
                <span className="text-muted-foreground">{currentNews.content}</span>
              </div>
            )}
          </div>
          {newsItems.length > 1 && (
            <div className="flex gap-1.5 flex-shrink-0">
              {newsItems.map((_, index) => (
                <button
                  key={index}
                  onClick={() => setCurrentIndex(index)}
                  className={`h-1.5 rounded-full transition-all ${
                    index === currentIndex ? "w-6 bg-primary" : "w-1.5 bg-muted-foreground/30 hover:bg-muted-foreground/50"
                  }`}
                  aria-label={`Go to news item ${index + 1}`}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

/**
 * Staff/Admin Top Bar with Timer
 */
function StaffTopBar({ userRole, userName, userEmail }: { userRole: "admin" | "staff"; userName: string; userEmail: string }) {
  const [systemStatus, setSystemStatus] = useState<"operational" | "degraded" | "down">("operational");
  const [activeTimer, setActiveTimer] = useState<ActiveTimer | null>(null);
  const [elapsedTime, setElapsedTime] = useState(0);
  const [isTimerDialogOpen, setIsTimerDialogOpen] = useState(false);
  const [isStopDialogOpen, setIsStopDialogOpen] = useState(false);
  const [timerDescription, setTimerDescription] = useState("");
  const [selectedClient, setSelectedClient] = useState<string>("");
  const [selectedRequest, setSelectedRequest] = useState<string>("");
  const [clients, setClients] = useState<Array<{ id: string; company_name: string }>>([]);
  const [requests, setRequests] = useState<Array<{ id: string; title: string }>>([]);
  const supabase = createClient();

  // Fetch clients for dropdown
  useEffect(() => {
    async function fetchClients() {
      const { data } = await supabase.from("clients").select("id, company_name").order("company_name");
      if (data) setClients(data);
    }
    fetchClients();
  }, []);

  // Fetch requests when client selected
  useEffect(() => {
    if (selectedClient) {
      async function fetchRequests() {
        const { data } = await supabase.from("requests").select("id, title").eq("client_id", selectedClient).order("created_at", { ascending: false });
        if (data) setRequests(data);
      }
      fetchRequests();
    } else {
      setRequests([]);
    }
  }, [selectedClient]);

  // Update elapsed time every second when timer is active
  useEffect(() => {
    if (!activeTimer) return;

    const interval = setInterval(() => {
      const elapsed = Math.floor((Date.now() - activeTimer.startedAt.getTime()) / 1000);
      setElapsedTime(elapsed);
    }, 1000);

    return () => clearInterval(interval);
  }, [activeTimer]);

  // Check system status
  useEffect(() => {
    async function checkStatus() {
      try {
        const response = await fetch("/api/health", { method: "HEAD" });
        setSystemStatus(response.ok ? "operational" : "degraded");
      } catch {
        setSystemStatus("degraded");
      }
    }

    checkStatus();
    const interval = setInterval(checkStatus, 60000); // Check every minute
    return () => clearInterval(interval);
  }, []);

  function startTimer() {
    if (!timerDescription.trim()) {
      toast.error("Please enter a description");
      return;
    }

    setActiveTimer({
      startedAt: new Date(),
      description: timerDescription,
      clientId: selectedClient || undefined,
      requestId: selectedRequest || undefined,
    });
    setElapsedTime(0);
    setIsTimerDialogOpen(false);
    setTimerDescription("");
    setSelectedClient("");
    setSelectedRequest("");
    toast.success("Timer started");
  }

  function pauseTimer() {
    // TODO: Implement pause functionality
    toast.info("Pause feature coming soon");
  }

  async function stopTimer() {
    if (!activeTimer) return;

    try {
      // Get current user ID
      const { data: { user }, error: userError } = await supabase.auth.getUser();
      
      if (userError || !user) {
        toast.error("User not authenticated");
        return;
      }

      const durationMinutes = Math.floor(elapsedTime / 60);

      // Save time entry to database
      const { error } = await supabase.from("time_entries").insert({
        user_id: user.id,  // Required field
        description: activeTimer.description,
        started_at: activeTimer.startedAt.toISOString(),
        ended_at: new Date().toISOString(),
        duration_minutes: durationMinutes,
        client_id: activeTimer.clientId || null,
        request_id: activeTimer.requestId || null,
        is_billable: true,
        status: "pending",
      });

      if (error) throw error;

      toast.success(`Time logged: ${formatTime(elapsedTime)}`);
      setActiveTimer(null);
      setElapsedTime(0);
      setIsStopDialogOpen(false);
    } catch (error) {
      console.error("Error saving time entry:", error);
      toast.error("Failed to save time entry");
    }
  }

  function formatTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
  }

  const roleConfig = {
    admin: { label: "Admin Dashboard", variant: "destructive" as const, icon: "👑" },
    staff: { label: "Staff Dashboard", variant: "default" as const, icon: "👤" },
    client: { label: "Client Dashboard", variant: "secondary" as const, icon: "👔" },
  };

  const statusConfig = {
    operational: { label: "All Systems Operational", icon: CheckCircle2, color: "text-green-500" },
    degraded: { label: "Performance Degraded", icon: AlertCircle, color: "text-yellow-500" },
    down: { label: "System Issues", icon: AlertCircle, color: "text-red-500" },
  };

  const StatusIcon = statusConfig[systemStatus].icon;

  return (
    <div className="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="flex h-16 items-center justify-between px-4 md:px-6">
        {/* Left: Role Badge */}
        <div className="flex items-center gap-4">
          <Badge variant={roleConfig[userRole].variant} className="gap-1.5 px-3 py-1.5">
            <span className="text-base">{roleConfig[userRole].icon}</span>
            <span className="font-semibold">{roleConfig[userRole].label}</span>
          </Badge>
          <div className="hidden md:block text-sm text-muted-foreground">
            {userName} <span className="text-xs">({userEmail})</span>
          </div>
        </div>

        {/* Right: System Status + Timer */}
        <div className="flex items-center gap-4">
          {/* System Status */}
          <div className="hidden sm:flex items-center gap-2 text-sm">
            <Activity className="h-4 w-4 text-muted-foreground" />
            <StatusIcon className={`h-4 w-4 ${statusConfig[systemStatus].color}`} />
            <span className="text-muted-foreground">{statusConfig[systemStatus].label}</span>
          </div>

          {/* Timer */}
          <div className="flex items-center gap-2">
            {!activeTimer ? (
              <Dialog open={isTimerDialogOpen} onOpenChange={setIsTimerDialogOpen}>
                <DialogTrigger asChild>
                  <Button variant="outline" size="sm" className="gap-2">
                    <Play className="h-4 w-4" />
                    <span className="hidden sm:inline">Start Timer</span>
                  </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-[500px]">
                  <DialogHeader>
                    <DialogTitle>Start Time Tracking</DialogTitle>
                    <DialogDescription>Track time spent on tasks, requests, or projects</DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4 pt-4">
                    <div>
                      <Label htmlFor="description">What are you working on?</Label>
                      <Textarea
                        id="description"
                        placeholder="e.g., Client meeting, Bug fix, Design review..."
                        value={timerDescription}
                        onChange={(e) => setTimerDescription(e.target.value)}
                        className="mt-2"
                      />
                    </div>

                    <div>
                      <Label htmlFor="client">Client (Optional)</Label>
                      <Select value={selectedClient} onValueChange={setSelectedClient}>
                        <SelectTrigger id="client" className="mt-2">
                          <SelectValue placeholder="Select client" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="">None</SelectItem>
                          {clients.map((client) => (
                            <SelectItem key={client.id} value={client.id}>
                              {client.company_name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    {selectedClient && (
                      <div>
                        <Label htmlFor="request">Request (Optional)</Label>
                        <Select value={selectedRequest} onValueChange={setSelectedRequest}>
                          <SelectTrigger id="request" className="mt-2">
                            <SelectValue placeholder="Select request" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            {requests.map((request) => (
                              <SelectItem key={request.id} value={request.id}>
                                {request.title}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    )}

                    <div className="flex gap-2 pt-2">
                      <Button onClick={startTimer} className="flex-1">
                        <Play className="mr-2 h-4 w-4" />
                        Start Timer
                      </Button>
                      <Button variant="outline" onClick={() => setIsTimerDialogOpen(false)}>
                        Cancel
                      </Button>
                    </div>
                  </div>
                </DialogContent>
              </Dialog>
            ) : (
              <div className="flex items-center gap-2">
                <div className="flex items-center gap-2 rounded-md bg-primary/10 px-3 py-1.5">
                  <Clock className="h-4 w-4 text-primary animate-pulse" />
                  <span className="font-mono text-sm font-semibold tabular-nums">{formatTime(elapsedTime)}</span>
                </div>
                {/* <Button variant="ghost" size="sm" onClick={pauseTimer}>
                  <Pause className="h-4 w-4" />
                </Button> */}
                <Dialog open={isStopDialogOpen} onOpenChange={setIsStopDialogOpen}>
                  <DialogTrigger asChild>
                    <Button variant="destructive" size="sm">
                      <Square className="h-4 w-4 mr-1.5" />
                      Stop
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Stop Timer</DialogTitle>
                      <DialogDescription>Save this time entry to your timesheet</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 pt-4">
                      <div className="rounded-lg bg-muted p-4">
                        <div className="flex items-center justify-between">
                          <span className="text-sm text-muted-foreground">Duration</span>
                          <span className="font-mono text-2xl font-bold">{formatTime(elapsedTime)}</span>
                        </div>
                        <div className="mt-2 text-sm text-muted-foreground">{activeTimer.description}</div>
                      </div>

                      <div className="flex gap-2">
                        <Button onClick={stopTimer} className="flex-1">
                          <Clock className="mr-2 h-4 w-4" />
                          Save Time Entry
                        </Button>
                        <Button variant="outline" onClick={() => setIsStopDialogOpen(false)}>
                          Cancel
                        </Button>
                      </div>
                    </div>
                  </DialogContent>
                </Dialog>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
