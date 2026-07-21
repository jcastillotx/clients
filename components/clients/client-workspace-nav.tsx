"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Building2, Files, Palette } from "lucide-react";
import { cn } from "@/lib/utils";

interface ClientWorkspaceNavProps {
  clientId: string;
  companyName: string;
}

const links = [
  { label: "Overview", suffix: "", icon: Building2 },
  { label: "Files", suffix: "/files", icon: Files },
  { label: "Brand guide", suffix: "/brand", icon: Palette },
] as const;

export function ClientWorkspaceNav({
  clientId,
  companyName,
}: ClientWorkspaceNavProps) {
  const pathname = usePathname();

  return (
    <nav
      aria-label={`${companyName} workspace`}
      className="flex w-full gap-1 overflow-x-auto rounded-lg border bg-muted/30 p-1"
    >
      {links.map(({ label, suffix, icon: Icon }) => {
        const href = `/clients/${clientId}${suffix}`;
        const isActive = pathname === href;

        return (
          <Link
            key={href}
            href={href}
            aria-current={isActive ? "page" : undefined}
            className={cn(
              "inline-flex min-h-9 shrink-0 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors",
              isActive
                ? "bg-background text-foreground shadow-sm"
                : "text-muted-foreground hover:bg-background/70 hover:text-foreground",
            )}
          >
            <Icon className="h-4 w-4" aria-hidden />
            {label}
          </Link>
        );
      })}
    </nav>
  );
}
