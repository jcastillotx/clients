import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { ReferralForm } from "@/components/referrals/referral-form";

export const metadata = {
  title: "Add Referral | Dashboard",
  description: "Log a new partner referral",
};

export default function NewReferralPage() {
  return (
    <div className="container mx-auto max-w-4xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Add Referral</h1>
          <p className="text-muted-foreground mt-1">Record a new referral from a partner.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/referrals">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Referrals
          </Link>
        </Button>
      </div>
      <ReferralForm />
    </div>
  );
}
