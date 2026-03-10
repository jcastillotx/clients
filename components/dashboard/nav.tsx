"use client";

import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { createClient } from "@/lib/supabase/client";
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
  CalendarCheck,
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
  logoUrl?: string | null;
  brandText?: string;
  siteTitle?: string;
  sidebarWidth?: "narrow" | "standard" | "wide";
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
      { name: "Invoices & Payments", href: "/invoices", icon: Receipt, access: "manager" },
    ],
  },
  {
    title: "Projects",
    items: [
      { name: "Project Requests", href: "/projects/requests", icon: ClipboardCheck },
      { name: "Project Timeline", href: "/projects", icon: CalendarRange },
      { name: "Project Messages", href: "/projects/messages", icon: MessageSquareText },
      { name: "Project Feedback", href: "/projects/feedback", icon: MessageCircleMore },
      { name: "Time Tracking", href: "/time-tracking", icon: Clock3, access: "staff" },
      { name: "Task Board", href: "/tasks", icon: Columns3, access: "staff" },
      { name: "Project Budgets", href: "/projects", icon: Briefcase, access: "staff" },
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
      { name: "Marketing Overview", href: "/marketing", icon: Megaphone },
      { name: "Campaigns", href: "/marketing/campaigns", icon: Megaphone, access: "staff" },
      { name: "Lead Management", href: "/marketing/leads", icon: Users, access: "staff" },
      { name: "Content Calendar", href: "/marketing/content-calendar", icon: CalendarDays, access: "staff" },
      { name: "Social Media", href: "/social-media", icon: Share2, access: "staff" },
      { name: "Ad Management", href: "/ads", icon: TrendingUp, access: "staff" },
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
    title: "Branding",
    items: [
      { name: "Portal Branding", href: "/branding", icon: Settings, access: "admin" },
    ],
  },
  {
    title: "Storage",
    items: [
      { name: "Storage Management", href: "/storage", icon: Database, access: "staff" },
    ],
  },
  {
    title: "Integrations",
    items: [
      { name: "Integrations", href: "/integrations", icon: LinkIcon, access: "admin" },
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
      { name: "Service Templates", href: "/admin/service-templates", icon: Briefcase, access: "admin" },
      { name: "Form Templates", href: "/admin/settings/templates", icon: FileText, access: "admin" },
      { name: "Email Provider", href: "/admin/email", icon: Mail, access: "admin" },
      { name: "Calendar", href: "/settings/calendar", icon: CalendarCheck, access: "staff" },
    ],
  },
];

const sidebarWidthClass: Record<string, string> = {
  narrow: "w-16 md:w-56",
  standard: "w-20 md:w-72",
  wide: "w-20 md:w-80",
};

export function DashboardNav({
  user,
  isStaff,
  isAdmin,
  isAccountManager,
  logoUrl,
  brandText,
  siteTitle = "Client Portal",
  sidebarWidth = "standard",
}: DashboardNavProps) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const router = useRouter();

  const handleLogout = async () => {
    const supabase = createClient();
    await supabase.auth.signOut();
    router.push("/login");
  };

  const widthClass = sidebarWidthClass[sidebarWidth] ?? sidebarWidthClass.standard;

  return (
    <aside
      className={`relative flex h-screen shrink-0 flex-col border-r border-border/70 backdrop-blur ${widthClass}`}
      style={{ backgroundColor: "var(--sidebar-bg)", color: "var(--sidebar-text)" }}
    >
      <div className="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white/5 to-transparent" />
      {/* Logo */}
      <div className="relative flex h-20 items-center px-6" style={{ borderBottom: "1px solid rgba(255,255,255,0.12)" }}>
        {logoUrl ? (
          <img
            src={logoUrl}
            alt="Company logo"
            className="h-11 w-11 rounded-xl object-contain p-1"
            style={{ border: "1px solid rgba(255,255,255,0.2)", backgroundColor: "rgba(255,255,255,0.1)" }}
          />
        ) : (
          <div
            className="flex h-11 w-11 items-center justify-center rounded-xl text-lg font-bold"
            style={{ border: "1px solid rgba(255,255,255,0.2)", backgroundColor: "rgba(255,255,255,0.15)", color: "var(--sidebar-text)" }}
          >
            {brandText ? brandText.slice(0, 1).toUpperCase() : "K"}
          </div>
        )}
        <div className="ml-3 hidden md:block">
          <h1 className="text-lg font-bold font-heading leading-none" style={{ color: "var(--sidebar-text)" }}>
            {brandText || "KRE8IV"}
          </h1>
          <p className="mt-1 text-xs uppercase tracking-[0.18em]" style={{ color: "var(--sidebar-text)", opacity: 0.55 }}>{siteTitle}</p>
        </div>
      </div>

      {/* Navigation */}
      <nav className="relative flex-1 space-y-6 overflow-y-auto px-4 py-5">
        <Link href="/dashboard">
          <Button
            variant="ghost"
            className={cn(
              "h-11 w-full justify-center rounded-xl px-3.5 text-[0.95rem] md:justify-start",
              "hover:bg-white/10 hover:text-[var(--sidebar-text)]",
              pathname === "/dashboard"
                ? "bg-white/15 font-semibold"
                : "font-normal opacity-90",
            )}
            style={{ color: "var(--sidebar-text)" }}
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
              <p
                className="mb-2 hidden px-2 text-xs font-semibold uppercase tracking-[0.14em] md:block"
                style={{ color: "var(--sidebar-text)", opacity: 0.5 }}
              >
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
                        variant="ghost"
                        className={cn(
                          "h-10 w-full justify-center rounded-xl px-3 text-[0.93rem] md:justify-start",
                          "hover:bg-white/10 hover:text-[var(--sidebar-text)]",
                          isActive
                            ? "bg-white/15 font-semibold"
                            : "font-normal opacity-85",
                        )}
                        style={{ color: "var(--sidebar-text)" }}
                      >
                        <item.icon className="h-4 w-4 md:mr-3 shrink-0" />
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
      <div className="relative border-t p-3 md:p-4" style={{ borderColor: "rgba(255,255,255,0.12)" }}>
        <div className="mb-3 hidden items-center gap-3 rounded-xl border p-2.5 md:flex" style={{ borderColor: "rgba(255,255,255,0.12)", backgroundColor: "rgba(255,255,255,0.08)" }}>
          <Avatar>
            <AvatarImage src={user.user_metadata?.avatar} />
            <AvatarFallback style={{ backgroundColor: "rgba(255,255,255,0.15)", color: "var(--sidebar-text)" }}>
              {user.user_metadata?.name
                ?.split(" ")
                .map((n) => n[0])
                .join("") || "U"}
            </AvatarFallback>
          </Avatar>
          <div className="flex-1 overflow-hidden">
            <p className="truncate text-sm font-medium" style={{ color: "var(--sidebar-text)" }}>{user.user_metadata?.name || "User"}</p>
            <p className="truncate text-xs" style={{ color: "var(--sidebar-text)", opacity: 0.6 }}>{user.email}</p>
          </div>
        </div>
        <Button
          variant="ghost"
          size="sm"
          className="w-full rounded-xl hover:bg-white/10"
          style={{ color: "var(--sidebar-text)", borderColor: "rgba(255,255,255,0.15)", border: "1px solid rgba(255,255,255,0.15)" }}
          onClick={handleLogout}
        >
          <LogOut className="h-4 w-4 md:mr-2" />
          <span className="hidden md:inline">Logout</span>
        </Button>
      </div>
    </aside>
  );
}
