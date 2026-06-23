import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { PartnerForm } from "@/components/partners/partner-form";

export const metadata = {
  title: "Add Partner | Dashboard",
  description: "Create a new partner organization",
};

export default function NewPartnerPage() {
  return (
    <div className="container mx-auto max-w-4xl py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Add Partner</h1>
          <p className="text-muted-foreground mt-1">Register a new partner organization and referral code.</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/partners">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Partners
          </Link>
        </Button>
      </div>
      <PartnerForm />
    </div>
  );
}
