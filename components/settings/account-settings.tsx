"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { createClient } from "@/lib/supabase/client";
import { Loader2, LogOut } from "lucide-react";
import { useRouter } from "next/navigation";

interface AccountSettingsProps {
  user: {
    id: string;
    email?: string;
  };
}

export function AccountSettings({ user: _user }: AccountSettingsProps) {
  const router = useRouter();
  const [isSigningOut, setIsSigningOut] = useState(false);

  const handleSignOut = async () => {
    setIsSigningOut(true);
    const supabase = createClient();
    await supabase.auth.signOut();
    router.push("/login");
  };

  return (
    <div className="space-y-6">
      {/* Sign Out */}
      <Card>
        <CardHeader>
          <CardTitle>Sign Out</CardTitle>
          <CardDescription>Sign out of your account on this device</CardDescription>
        </CardHeader>
        <CardContent>
          <Button variant="destructive" onClick={handleSignOut} disabled={isSigningOut}>
            {isSigningOut ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <LogOut className="mr-2 h-4 w-4" />}
            Sign Out
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
