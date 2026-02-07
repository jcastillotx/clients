"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { LayoutDashboard, Building2, FileText, Receipt, FolderOpen, Settings, LogOut } from "lucide-react";

interface DashboardNavProps {
  user: {
    id: string;
    email?: string;
    user_metadata?: {
      name?: string;
      avatar?: string;
    };
  };
}

const navigation = [
  { name: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { name: "Clients", href: "/clients", icon: Building2 },
  { name: "Requests", href: "/requests", icon: FileText },
  { name: "Invoices", href: "/invoices", icon: Receipt },
  { name: "Documents", href: "/documents", icon: FolderOpen },
  { name: "Settings", href: "/settings", icon: Settings },
];

export function DashboardNav({ user }: DashboardNavProps) {
  const pathname = usePathname();

  return (
    <aside className="relative flex h-screen w-20 shrink-0 flex-col border-r border-border/70 bg-card/75 backdrop-blur md:w-72">
      <div className="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-primary/15 to-transparent" />
      {/* Logo */}
      <div className="relative flex h-20 items-center border-b border-border/60 px-6">
        <div className="flex h-11 w-11 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-lg font-bold text-primary">
          K
        </div>
        <div className="ml-3 hidden md:block">
          <h1 className="text-lg font-bold font-heading leading-none">KRE8IV</h1>
          <p className="mt-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">Client Portal</p>
        </div>
      </div>

      {/* Navigation */}
      <nav className="relative flex-1 space-y-1.5 px-4 py-5">
        {navigation.map((item) => {
          const isActive = pathname === item.href || pathname.startsWith(`${item.href}/`);
          return (
            <Link key={item.name} href={item.href}>
              <Button
                variant={isActive ? "secondary" : "ghost"}
                className={cn(
                  "h-11 w-full justify-center rounded-xl px-3.5 text-[0.95rem] md:justify-start",
                  isActive && "bg-primary/12 text-primary shadow-sm",
                )}
              >
                <item.icon className="h-4 w-4 md:mr-3" />
                <span className="hidden md:inline">{item.name}</span>
              </Button>
            </Link>
          );
        })}
      </nav>

      {/* User section */}
      <div className="relative border-t border-border/60 p-3 md:p-4">
        <div className="mb-3 hidden items-center gap-3 rounded-xl border border-border/70 bg-background/75 p-2.5 md:flex">
          <Avatar>
            <AvatarImage src={user.user_metadata?.avatar} />
            <AvatarFallback>
              {user.user_metadata?.name
                ?.split(" ")
                .map((n) => n[0])
                .join("") || "U"}
            </AvatarFallback>
          </Avatar>
          <div className="flex-1 overflow-hidden">
            <p className="truncate text-sm font-medium">{user.user_metadata?.name || "User"}</p>
            <p className="truncate text-xs text-muted-foreground">{user.email}</p>
          </div>
        </div>
        <Button variant="outline" size="sm" className="w-full rounded-xl">
          <LogOut className="h-4 w-4 md:mr-2" />
          <span className="hidden md:inline">Logout</span>
        </Button>
      </div>
    </aside>
  );
}
