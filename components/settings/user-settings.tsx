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
import { Loader2, Trash2, User } from "lucide-react";
import { fetchApi } from "@/lib/api/client";
import { toast } from "sonner";

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
  const [isDeletingAvatar, setIsDeletingAvatar] = useState(false);
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

      toast.success("Profile updated successfully!");
      router.refresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to update profile");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleAvatarUpload = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setIsUploadingAvatar(true);

    try {
      const formData = new FormData();
      formData.append("file", file);

      const payload = await fetchApi<{ avatarUrl: string }>(
        "/api/settings/profile-image",
        { method: "POST", body: formData },
        { fallbackMessage: "Failed to upload profile image" },
      );

      if (!payload.avatarUrl) {
        throw new Error("Failed to upload profile image");
      }

      setAvatarUrl(payload.avatarUrl);
      toast.success("Profile image updated!");
      router.refresh();
    } catch (uploadError) {
      toast.error(uploadError instanceof Error ? uploadError.message : "Failed to upload profile image");
    } finally {
      setIsUploadingAvatar(false);
      event.target.value = "";
    }
  };

  const handleAvatarDelete = async () => {
    setIsDeletingAvatar(true);
    try {
      await fetchApi("/api/settings/profile-image", { method: "DELETE" }, {
        fallbackMessage: "Failed to delete profile image",
      });
      setAvatarUrl("");
      toast.success("Profile image removed.");
      router.refresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to delete profile image");
    } finally {
      setIsDeletingAvatar(false);
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
              {isUploadingAvatar && (
                <p className="mt-2 inline-flex items-center text-xs text-muted-foreground">
                  <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                  Uploading avatar...
                </p>
              )}
              {avatarUrl && !isUploadingAvatar && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="mt-2 text-destructive hover:text-destructive"
                  onClick={handleAvatarDelete}
                  disabled={isDeletingAvatar}
                >
                  {isDeletingAvatar ? (
                    <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                  ) : (
                    <Trash2 className="mr-2 h-3 w-3" />
                  )}
                  Remove photo
                </Button>
              )}
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
