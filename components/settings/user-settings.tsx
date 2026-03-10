"use client";

import { type ChangeEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { createClient } from "@/lib/supabase/client";
import { Loader2, User } from "lucide-react";

const profileSchema = z.object({
  name: z.string().min(2, "Name must be at least 2 characters"),
  phone: z.string().optional(),
});

type ProfileFormInput = z.infer<typeof profileSchema>;

interface UserSettingsProps {
  user: {
    id?: string;
    name?: string;
    email?: string;
    phone?: string;
    avatar?: string;
  };
}

export function UserSettings({ user }: UserSettingsProps) {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [avatarUrl, setAvatarUrl] = useState(user.avatar || "");

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ProfileFormInput>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user.name || "",
      phone: user.phone || "",
    },
  });

  const onSubmit = async (data: ProfileFormInput) => {
    setIsSubmitting(true);
    setError(null);
    setSuccess(false);

    try {
      const supabase = createClient();

      // Update user metadata
      const { error: updateError } = await supabase.auth.updateUser({
        data: {
          name: data.name,
          phone: data.phone,
          avatar: avatarUrl || null,
        },
      });

      if (updateError) throw updateError;

      // Also update users table if user.id exists
      if (user.id) {
        const { error: dbError } = await supabase
          .from("users")
          .update({
            name: data.name,
            phone: data.phone,
          })
          .eq("id", user.id);

        if (dbError) throw dbError;
      }

      setSuccess(true);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to update profile");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleAvatarUpload = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setError(null);
    setSuccess(false);
    setIsUploadingAvatar(true);

    try {
      // Step 1: Get presigned PUT URL from server
      let presignPayload: { presignedUrl?: string; key?: string; error?: string } = {};
      try {
        const presignRes = await fetch("/api/settings/profile-image/presign", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ contentType: file.type, fileSize: file.size }),
        });
        presignPayload = await presignRes.json();
        if (!presignRes.ok || !presignPayload.presignedUrl || !presignPayload.key) {
          throw new Error(presignPayload.error || "Failed to get upload URL");
        }
      } catch (err) {
        throw new Error(`Step 1 (get upload URL): ${err instanceof Error ? err.message : String(err)}`);
      }

      // Step 2: Upload file directly to S3 (bypasses Vercel size limits)
      try {
        const s3Res = await fetch(presignPayload.presignedUrl!, {
          method: "PUT",
          headers: { "Content-Type": file.type },
          body: file,
        });
        if (!s3Res.ok) {
          const errText = await s3Res.text().catch(() => s3Res.status.toString());
          throw new Error(`S3 returned ${s3Res.status}: ${errText}`);
        }
      } catch (err) {
        throw new Error(`Step 2 (upload to S3): ${err instanceof Error ? err.message : String(err)}`);
      }

      // Step 3: Save the S3 key to the database
      let savePayload: { avatarUrl?: string; error?: string } = {};
      try {
        const saveRes = await fetch("/api/settings/profile-image", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ key: presignPayload.key }),
        });
        savePayload = await saveRes.json();
        if (!saveRes.ok || !savePayload.avatarUrl) {
          throw new Error(savePayload.error || "Failed to save profile image");
        }
      } catch (err) {
        throw new Error(`Step 3 (save to DB): ${err instanceof Error ? err.message : String(err)}`);
      }

      setAvatarUrl(savePayload.avatarUrl!);
      setSuccess(true);
      router.refresh();
    } catch (uploadError) {
      setError(uploadError instanceof Error ? uploadError.message : "Failed to upload profile image");
    } finally {
      setIsUploadingAvatar(false);
      event.target.value = "";
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Profile Information</CardTitle>
        <CardDescription>Update your personal information</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {error && <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}

          {success && (
            <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">Profile updated successfully!</div>
          )}

          {/* Avatar */}
          <div className="flex items-center gap-4">
            <Avatar className="h-20 w-20">
              <AvatarImage src={avatarUrl || undefined} />
              <AvatarFallback>
                <User className="h-10 w-10" />
              </AvatarFallback>
            </Avatar>
            <div>
              <Label htmlFor="avatar-upload" className="sr-only">
                Upload profile image
              </Label>
              <Input
                id="avatar-upload"
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.gif"
                className="max-w-xs"
                onChange={handleAvatarUpload}
                disabled={isUploadingAvatar}
              />
              {isUploadingAvatar ? (
                <p className="mt-2 inline-flex items-center text-xs text-muted-foreground">
                  <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                  Uploading avatar...
                </p>
              ) : null}
              <p className="text-xs text-muted-foreground mt-1">Use JPG, PNG, WEBP, or GIF up to 5MB.</p>
            </div>
          </div>

          {/* Email (read-only) */}
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={user.email} disabled />
            <p className="text-xs text-muted-foreground">Email cannot be changed from this page</p>
          </div>

          {/* Name */}
          <div className="space-y-2">
            <Label htmlFor="name">
              Full Name <span className="text-destructive">*</span>
            </Label>
            <Input id="name" placeholder="John Doe" {...register("name")} />
            {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
          </div>

          {/* Phone */}
          <div className="space-y-2">
            <Label htmlFor="phone">Phone Number</Label>
            <Input id="phone" type="tel" placeholder="+1 (555) 123-4567" {...register("phone")} />
            {errors.phone && <p className="text-sm text-destructive">{errors.phone.message}</p>}
          </div>

          {/* Actions */}
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Save Changes
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
