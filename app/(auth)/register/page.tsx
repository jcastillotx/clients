import Link from "next/link";

import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Invitation Only | KRE8IV",
  description: "Access to the KRE8IV client portal is by invitation only.",
};

// Middleware redirects authenticated users from "/register" to "/dashboard".
export default function RegisterPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-muted/40 px-4">
      <Card className="w-full max-w-md text-center">
        <CardHeader>
          <CardTitle className="text-2xl">Access by invitation only</CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <p className="text-sm text-muted-foreground">
            Client portal accounts are created by the KRE8IV team. Contact your
            account manager to request access or receive your login instructions.
          </p>
          <Link href="/login" className={buttonVariants()}>
            Return to sign in
          </Link>
        </CardContent>
      </Card>
    </main>
  );
}
