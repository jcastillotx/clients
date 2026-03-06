"use client";

import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  LayoutDashboard,
  FileText,
  Receipt,
  Settings,
  LogOut,
  Megaphone,
  Sparkles,
  Bot,
  ClipboardCheck,
  HelpCircle,
  ShieldCheck,
  Clock3,
  Columns3,
  CalendarRange,
  Briefcase,
  MessageSquareText,
  CalendarDays,
  NotebookText,
  Mail,
  Share2,
  TrendingUp,
  Globe,
  BrainCircuit,
  BarChart3,
  Users,
  Link as LinkIcon,
  Star,
  GraduationCap,
  MessageCircleMore,
  Heart,
  Database,
  FolderOpen,
  Shield,
  EyeOff,
  Cog,
} from "lucide-react";
import { ComponentType } from "react";

interface DashboardNavProps {
  user: {
    id: string;
    email?: string;
    user_metadata?: {
      name?: string;
      avatar?: string;
    };
  };
  isStaff: boolean;
  isAdmin: boolean;
  isAccountManager: boolean;
}

type AccessLevel = "all" | "staff" | "admin" | "manager";

interface NavItem {
  name: string;
  href: string;
  icon: ComponentType<{ className?: string }>;
  access?: AccessLevel;
  matchesTab?: string;
}

interface NavSection {
  title: string;
  items: NavItem[];
}

function canAccessItem(access: AccessLevel | undefined, isStaff: boolean, isAdmin: boolean, isAccountManager: boolean) {
  if (!access || access === "all") return true;
  if (access === "staff") return isStaff || isAdmin;
  if (access === "manager") return isAdmin || isAccountManager;
  return isAdmin;
}

const navigationSections: NavSection[] = [
  {
    title: "Services",
    items: [
      { name: "Service Requests", href: "/requests", icon: ClipboardCheck },
      { name: "Support Tickets", href: "/support", icon: HelpCircle },
      { name: "Maintenance Plans", href: "/maintenance-plans", icon: ShieldCheck },
      { name: "File Storage", href: "/documents", icon: FolderOpen },
      { name: "Proposals", href: "/proposals", icon: FileText },
      { name: "Invoices & Payments", href: "/invoices", icon: Receipt },
    ],
  },
  {
    title: "Projects & Time",
    items: [
      { name: "Time Tracking", href: "/time-tracking", icon: Clock3 },
      { name: "Task Board", href: "/tasks", icon: Columns3 },
      { name: "Project Requests", href: "/projects/requests", icon: ClipboardCheck },
      { name: "Project Timeline", href: "/projects", icon: CalendarRange },
      { name: "Project Budgets", href: "/projects", icon: Briefcase },
      { name: "Staff Tasks", href: "/tasks", icon: ClipboardCheck, access: "staff" },
    ],
  },
  {
    title: "Communication",
    items: [
      { name: "Messages", href: "/messages", icon: MessageSquareText },
      { name: "Meetings", href: "/meetings", icon: CalendarDays, access: "staff" },
      { name: "Meeting Notes", href: "/meetings", icon: NotebookText, access: "staff" },
      { name: "Email Assistant", href: "/ai/email-assistant", icon: Mail, access: "staff" },
    ],
  },
  {
    title: "Marketing",
    items: [
      { name: "Marketing Tools", href: "/marketing/campaigns", icon: Megaphone },
      { name: "Lead Management", href: "/marketing/leads", icon: Users },
      { name: "Content Calendar", href: "/marketing/content-calendar", icon: CalendarDays },
      { name: "Social Media", href: "/social-media", icon: Share2 },
      { name: "Ad Management", href: "/ads", icon: TrendingUp },
      { name: "Brand Monitoring", href: "/brand/monitoring", icon: Globe, access: "staff" },
    ],
  },
  {
    title: "AI & Automation",
    items: [
      { name: "AI Management", href: "/ai/workflows", icon: BrainCircuit, access: "staff" },
      { name: "AI Assistant", href: "/ai/assistant", icon: Bot, access: "staff" },
      { name: "Automation", href: "/automation", icon: Sparkles, access: "staff" },
      { name: "AI Analytics", href: "/ai/analytics", icon: BarChart3, access: "staff" },
    ],
  },
  {
    title: "Reports",
    items: [
      { name: "Reports Dashboard", href: "/reports", icon: BarChart3, access: "staff" },
      { name: "Team Workload", href: "/time-tracking/reports", icon: Users, access: "staff" },
      { name: "Client Reports", href: "/reports/custom", icon: FileText, access: "staff" },
      { name: "Activity Log", href: "/reports", icon: Clock3, access: "staff" },
    ],
  },
  {
    title: "Management",
    items: [
      { name: "Clients", href: "/clients", icon: Briefcase, access: "staff" },
      { name: "Users", href: "/users", icon: Users, access: "manager" },
      { name: "Partners", href: "/partners", icon: LinkIcon, access: "staff" },
      { name: "Referrals", href: "/referrals", icon: Star, access: "staff" },
      { name: "Staff Guides", href: "/staff-guides", icon: GraduationCap, access: "staff" },
      { name: "Feedback & Surveys", href: "/surveys", icon: MessageCircleMore, access: "staff" },
      { name: "Account Health", href: "/account-health", icon: Heart, access: "staff" },
    ],
  },
  {
    title: "Storage & Integration",
    items: [
      { name: "Storage Management", href: "/settings?tab=storage", icon: Database, access: "staff" },
      { name: "Webhooks", href: "/webhooks", icon: LinkIcon, access: "admin" },
    ],
  },
  {
    title: "Security",
    items: [
      { name: "Security Overview", href: "/security-overview", icon: Shield, access: "staff" },
      { name: "Privacy Requests", href: "/privacy-requests", icon: EyeOff, access: "staff" },
    ],
  },
  {
    title: "Settings",
    items: [
      { name: "Profile", href: "/settings?tab=profile", icon: Users },
      { name: "Password", href: "/settings?tab=account", icon: Shield },
      { name: "System Settings", href: "/settings", icon: Cog, access: "staff", matchesTab: "profile" },
      { name: "Maintenance Plans", href: "/admin/maintenance-plans", icon: ShieldCheck, access: "admin" },
      { name: "Form Templates", href: "/admin/settings/templates", icon: FileText, access: "admin" },
      { name: "White Label", href: "/settings?tab=branding", icon: Settings, access: "admin" },
    ],
  },
];

export function DashboardNav({ user, isStaff, isAdmin, isAccountManager }: DashboardNavProps) {
  const pathname = usePathname();
  const searchParams = useSearchParams();

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
      <nav className="relative flex-1 space-y-6 overflow-y-auto px-4 py-5">
        <Link href="/dashboard">
          <Button
            variant={pathname === "/dashboard" ? "secondary" : "ghost"}
            className={cn(
              "h-11 w-full justify-center rounded-xl px-3.5 text-[0.95rem] md:justify-start",
              pathname === "/dashboard" && "bg-primary/12 text-primary shadow-sm",
            )}
          >
            <LayoutDashboard className="h-4 w-4 md:mr-3" />
            <span className="hidden md:inline">Dashboard</span>
          </Button>
        </Link>

        {navigationSections.map((section) => {
          const visibleItems = section.items.filter((item) =>
            canAccessItem(item.access, isStaff, isAdmin, isAccountManager),
          );
          if (visibleItems.length === 0) return null;

          return (
            <div key={section.title}>
            <p className="mb-2 hidden px-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground/90 md:block">
              {section.title}
            </p>
            <div className="space-y-1.5">
              {visibleItems.map((item) => {
                const [itemPathname, itemSearch] = item.href.split("?");
                const itemTab = item.matchesTab ?? new URLSearchParams(itemSearch).get("tab");
                const currentTab = searchParams.get("tab");
                const isTabMatch = itemTab ? currentTab === itemTab : true;
                const isActive = (pathname === itemPathname && isTabMatch) || pathname.startsWith(`${itemPathname}/`);
                return (
                  <Link key={item.name} href={item.href}>
                    <Button
                      variant={isActive ? "secondary" : "ghost"}
                      className={cn(
                        "h-10 w-full justify-center rounded-xl px-3 text-[0.93rem] md:justify-start",
                        isActive && "bg-primary/12 text-primary shadow-sm",
                      )}
                    >
                      <item.icon className="h-4 w-4 md:mr-3" />
                      <span className="hidden md:inline">{item.name}</span>
                    </Button>
                  </Link>
                );
              })}
            </div>
          </div>
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
