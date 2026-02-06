"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { createMeetingSchema, type CreateMeetingInput } from "@/lib/validations/meeting";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Loader2, Plus, X, User, Video, MapPin } from "lucide-react";

interface MeetingFormProps {
  clients: Array<{
    id: string;
    company_name: string;
  }>;
  users: Array<{
    id: string;
    name: string;
    email: string;
  }>;
  preselectedClientId?: string;
  preselectedRequestId?: string;
}

interface AttendeeInput {
  userId?: string;
  name: string;
  email: string;
  role?: string;
  isExternal?: boolean;
}

export function MeetingForm({ clients, users, preselectedClientId, preselectedRequestId }: MeetingFormProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [attendees, setAttendees] = useState<AttendeeInput[]>([]);
  const [newAttendee, setNewAttendee] = useState<AttendeeInput>({
    name: "",
    email: "",
    role: "",
    isExternal: false,
  });

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useForm<CreateMeetingInput>({
    resolver: zodResolver(createMeetingSchema),
    defaultValues: {
      clientId: preselectedClientId || "",
      requestId: preselectedRequestId || null,
      meetingType: "discovery",
      durationMinutes: 60,
    },
  });

  const clientId = watch("clientId");
  const meetingType = watch("meetingType");
  const meetingUrl = watch("meetingUrl");
  const location = watch("location");

  const addAttendee = (fromUser?: { id: string; name: string; email: string }) => {
    if (fromUser) {
      const attendee: AttendeeInput = {
        userId: fromUser.id,
        name: fromUser.name,
        email: fromUser.email,
        isExternal: false,
      };
      setAttendees([...attendees, attendee]);
    } else if (newAttendee.name && newAttendee.email) {
      setAttendees([...attendees, { ...newAttendee, isExternal: true }]);
      setNewAttendee({ name: "", email: "", role: "", isExternal: false });
    }
  };

  const removeAttendee = (index: number) => {
    setAttendees(attendees.filter((_, i) => i !== index));
  };

  const onSubmit = async (data: CreateMeetingInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const response = await fetch("/api/meetings", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ...data,
          attendees: attendees.length > 0 ? attendees : undefined,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || "Failed to create meeting");
      }

      const meeting = await response.json();
      router.push(`/meetings/${meeting.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to create meeting");
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

      <Card>
        <CardHeader>
          <CardTitle>Meeting Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Client Selection */}
          <div className="space-y-2">
            <Label htmlFor="clientId">
              Client <span className="text-destructive">*</span>
            </Label>
            <Select value={clientId} onValueChange={(value) => setValue("clientId", value)}>
              <SelectTrigger id="clientId">
                <SelectValue placeholder="Select a client" />
              </SelectTrigger>
              <SelectContent>
                {clients.map((client) => (
                  <SelectItem key={client.id} value={client.id}>
                    {client.company_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.clientId && <p className="text-sm text-destructive">{errors.clientId.message}</p>}
          </div>

          {/* Title */}
          <div className="space-y-2">
            <Label htmlFor="title">
              Title <span className="text-destructive">*</span>
            </Label>
            <Input id="title" placeholder="e.g., Q4 Business Review" {...register("title")} />
            {errors.title && <p className="text-sm text-destructive">{errors.title.message}</p>}
          </div>

          {/* Description */}
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="Meeting description and objectives..."
              rows={3}
              {...register("description")}
            />
            {errors.description && <p className="text-sm text-destructive">{errors.description.message}</p>}
          </div>

          {/* Meeting Type */}
          <div className="space-y-2">
            <Label htmlFor="meetingType">
              Meeting Type <span className="text-destructive">*</span>
            </Label>
            <Select value={meetingType} onValueChange={(value) => setValue("meetingType", value as any)}>
              <SelectTrigger id="meetingType">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="discovery">Discovery</SelectItem>
                <SelectItem value="planning">Planning</SelectItem>
                <SelectItem value="review">Review</SelectItem>
                <SelectItem value="qbr">QBR</SelectItem>
                <SelectItem value="standup">Standup</SelectItem>
                <SelectItem value="demo">Demo</SelectItem>
                <SelectItem value="training">Training</SelectItem>
                <SelectItem value="support">Support</SelectItem>
                <SelectItem value="other">Other</SelectItem>
              </SelectContent>
            </Select>
            {errors.meetingType && <p className="text-sm text-destructive">{errors.meetingType.message}</p>}
          </div>

          {/* Date, Time, and Duration */}
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="scheduledAt">
                Date & Time <span className="text-destructive">*</span>
              </Label>
              <Input id="scheduledAt" type="datetime-local" {...register("scheduledAt")} />
              {errors.scheduledAt && <p className="text-sm text-destructive">{errors.scheduledAt.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="durationMinutes">Duration (minutes)</Label>
              <Input
                id="durationMinutes"
                type="number"
                min={15}
                max={480}
                {...register("durationMinutes", { valueAsNumber: true })}
              />
              {errors.durationMinutes && <p className="text-sm text-destructive">{errors.durationMinutes.message}</p>}
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Location & Access</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Meeting URL */}
          <div className="space-y-2">
            <Label htmlFor="meetingUrl" className="flex items-center gap-2">
              <Video className="h-4 w-4" />
              Video Meeting URL
            </Label>
            <Input id="meetingUrl" type="url" placeholder="https://zoom.us/j/123456789" {...register("meetingUrl")} />
            {errors.meetingUrl && <p className="text-sm text-destructive">{errors.meetingUrl.message}</p>}
          </div>

          {/* Physical Location */}
          <div className="space-y-2">
            <Label htmlFor="location" className="flex items-center gap-2">
              <MapPin className="h-4 w-4" />
              Physical Location
            </Label>
            <Input id="location" placeholder="Conference Room A, 123 Main St" {...register("location")} />
            {errors.location && <p className="text-sm text-destructive">{errors.location.message}</p>}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Attendees</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Current Attendees */}
          {attendees.length > 0 && (
            <div className="space-y-2">
              {attendees.map((attendee, index) => (
                <div key={index} className="flex items-center justify-between p-3 bg-muted rounded-md">
                  <div className="flex items-center gap-3">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="font-medium">{attendee.name}</div>
                      <div className="text-sm text-muted-foreground">{attendee.email}</div>
                    </div>
                    {attendee.isExternal && (
                      <Badge variant="outline" className="ml-2">
                        External
                      </Badge>
                    )}
                  </div>
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeAttendee(index)}>
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              ))}
            </div>
          )}

          {/* Add Internal User */}
          <div className="space-y-2">
            <Label>Add Team Member</Label>
            <div className="flex gap-2">
              <Select
                onValueChange={(userId) => {
                  const user = users.find((u) => u.id === userId);
                  if (user) addAttendee(user);
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a team member" />
                </SelectTrigger>
                <SelectContent>
                  {users
                    .filter((user) => !attendees.find((a) => a.userId === user.id))
                    .map((user) => (
                      <SelectItem key={user.id} value={user.id}>
                        {user.name} ({user.email})
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Add External Attendee */}
          <div className="space-y-2">
            <Label>Add External Attendee</Label>
            <div className="grid gap-2 md:grid-cols-3">
              <Input
                placeholder="Name"
                value={newAttendee.name}
                onChange={(e) => setNewAttendee({ ...newAttendee, name: e.target.value })}
              />
              <Input
                type="email"
                placeholder="Email"
                value={newAttendee.email}
                onChange={(e) => setNewAttendee({ ...newAttendee, email: e.target.value })}
              />
              <Button
                type="button"
                variant="outline"
                onClick={() => addAttendee()}
                disabled={!newAttendee.name || !newAttendee.email}
              >
                <Plus className="mr-2 h-4 w-4" />
                Add
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Agenda</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <Textarea
              id="agenda"
              placeholder="Meeting agenda and topics to discuss..."
              rows={6}
              {...register("agenda")}
            />
            {errors.agenda && <p className="text-sm text-destructive">{errors.agenda.message}</p>}
          </div>
        </CardContent>
      </Card>

      {/* Actions */}
      <div className="flex gap-4">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Schedule Meeting
        </Button>
        <Button type="button" variant="outline" onClick={() => router.back()}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
