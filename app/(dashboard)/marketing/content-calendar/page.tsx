import { Suspense } from "react";
import { ContentCalendar } from "@/components/marketing/content-calendar";
import { Button } from "@/components/ui/button";
import { PlusCircle } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Content Calendar | Marketing",
  description: "Plan and schedule your content",
};

export default function ContentCalendarPage() {
  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Content Calendar</h1>
          <p className="text-muted-foreground">Plan and schedule content across all your marketing channels</p>
        </div>
        <Link href="/marketing/content-calendar/new">
          <Button>
            <PlusCircle className="mr-2 h-4 w-4" />
            New Content
          </Button>
        </Link>
      </div>

      <Suspense fallback={<div>Loading calendar...</div>}>
        <ContentCalendar />
      </Suspense>
    </div>
  );
}
