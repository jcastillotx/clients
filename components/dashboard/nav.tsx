"use client";

import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { createClient } from "@/lib/supabase/client";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  LayoutDashboard,
  FileText,
  FileSignature,
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
  CalendarCheck,
  Archive,
  BookOpen,
  DollarSign,
  Menu,
  X,
  ChevronDown,
  Wand2,
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
  /** Match `?tab=` query param; use `defaultWhenNoTab` when href has no tab. */
  matchesTab?: string;
  /** Highlight when pathname matches and no `tab` query is present. */
  defaultWhenNoTab?: boolean;
  /** Only highlight on exact pathname (not child routes). */
  exactMatch?: boolean;
}

interface NavSection {
  title: string;
  items: NavItem[];
}

function canAccessItem(
  access: AccessLevel | undefined,
  isStaff: boolean,
  isAdmin: boolean,
  isAccountManager: boolean,
) {
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
      {
        name: "Maintenance Plans",
        href: "/maintenance-plans",
        icon: ShieldCheck,
      },
      { name: "File Storage", href: "/documents", icon: FolderOpen },
      { name: "Contracts", href: "/contracts", icon: FileSignature, access: "staff" },
      { name: "Proposals", href: "/proposals", icon: FileText },
      {
        name: "Invoices & Payments",
        href: "/invoices",
        icon: Receipt,
        access: "manager",
      },
    ],
  },
  {
    title: "Projects",
    items: [
      {
        name: "Project Requests",
        href: "/projects/requests",
        icon: ClipboardCheck,
      },
      { name: "Project Timeline", href: "/projects", icon: CalendarRange, defaultWhenNoTab: true },
      {
        name: "Project Messages",
        href: "/projects/messages",
        icon: MessageSquareText,
      },
      {
        name: "Project Feedback",
        href: "/projects/feedback",
        icon: MessageCircleMore,
      },
      {
        name: "Time Tracking",
        href: "/time-tracking",
        icon: Clock3,
        access: "staff",
      },
      { name: "Task Board", href: "/tasks", icon: Columns3, access: "staff" },
      {
        name: "Project Budgets",
        href: "/projects/budgets",
        icon: DollarSign,
        access: "staff",
      },
    ],
  },
  {
    title: "Communication",
    items: [
      { name: "Messages", href: "/messages", icon: MessageSquareText },
      {
        name: "Meetings",
        href: "/meetings",
        icon: CalendarDays,
        access: "staff",
        defaultWhenNoTab: true,
      },
      {
        name: "Meeting Notes",
        href: "/meetings?tab=list",
        icon: NotebookText,
        access: "staff",
        matchesTab: "list",
      },
      {
        name: "Email Assistant",
        href: "/ai/email-assistant",
        icon: Mail,
        access: "staff",
      },
    ],
  },
  {
    title: "Marketing",
    items: [
      { name: "Marketing Overview", href: "/marketing", icon: Megaphone },
      {
        name: "Campaigns",
        href: "/marketing/campaigns",
        icon: Megaphone,
        access: "staff",
      },
      {
        name: "Lead Management",
        href: "/marketing/leads",
        icon: Users,
        access: "staff",
      },
      {
        name: "Content Calendar",
        href: "/marketing/content-calendar",
        icon: CalendarDays,
        access: "staff",
      },
      {
        name: "Social Media",
        href: "/social-media",
        icon: Share2,
        access: "staff",
      },
      {
        name: "Ad Management",
        href: "/ads",
        icon: TrendingUp,
        access: "staff",
      },
      {
        name: "Brand Monitoring",
        href: "/brand/monitoring",
        icon: Globe,
        access: "staff",
      },
      {
        name: "Brand Guide",
        href: "/brand/guide",
        icon: BookOpen,
        access: "staff",
      },
      {
        name: "Brand Competitors",
        href: "/brand/competitors",
        icon: Users,
        access: "staff",
      },
    ],
  },
  {
    title: "AI & Automation",
    items: [
      {
        name: "AI Management",
        href: "/ai/workflows",
        icon: BrainCircuit,
        access: "staff",
      },
      {
        name: "AI Assistant",
        href: "/ai/assistant",
        icon: Bot,
        access: "staff",
      },
      {
        name: "Design Studio",
        href: "/ai/design",
        icon: Wand2,
        access: "staff",
      },
      {
        name: "Automation",
        href: "/automation",
        icon: Sparkles,
        access: "staff",
      },
      {
        name: "AI Analytics",
        href: "/ai/analytics",
        icon: BarChart3,
        access: "staff",
      },
    ],
  },
  {
    title: "Reports",
    items: [
      {
        name: "Reports Dashboard",
        href: "/reports",
        icon: BarChart3,
        access: "staff",
        defaultWhenNoTab: true,
      },
      {
        name: "Team Workload",
        href: "/time-tracking/reports",
        icon: Users,
        access: "staff",
      },
      {
        name: "Client Reports",
        href: "/reports/custom",
        icon: FileText,
        access: "staff",
      },
    ],
  },
  {
    title: "Management",
    items: [
      { name: "Clients", href: "/clients", icon: Briefcase, access: "staff" },
      {
        name: "Archive",
        href: "/admin/archive",
        icon: Archive,
        access: "admin",
      },
      { name: "Users", href: "/users", icon: Users, access: "manager" },
      { name: "Partners", href: "/partners", icon: LinkIcon, access: "staff" },
      { name: "Referrals", href: "/referrals", icon: Star, access: "staff" },
      {
        name: "Staff Guides",
        href: "/staff-guides",
        icon: GraduationCap,
        access: "staff",
      },
      {
        name: "Feedback & Surveys",
        href: "/surveys",
        icon: MessageCircleMore,
        access: "staff",
      },
      {
        name: "Account Health",
        href: "/account-health",
        icon: Heart,
        access: "staff",
      },
      {
        name: "Knowledge Base",
        href: "/knowledge-base",
        icon: BookOpen,
        access: "staff",
      },
    ],
  },
  {
    title: "Administration",
    items: [
      { name: "Admin Dashboard", href: "/admin", icon: LayoutDashboard, access: "admin", exactMatch: true },
      { name: "Roles & Permissions", href: "/admin/roles", icon: Shield, access: "admin" },
      { name: "Feature Flags", href: "/admin/features", icon: Sparkles, access: "admin" },
      { name: "Financial Overview", href: "/admin/financial", icon: DollarSign, access: "admin" },
      { name: "All Support Tickets", href: "/admin/tickets", icon: HelpCircle, access: "admin" },
      {
        name: "Invoice & Email Templates",
        href: "/admin/settings/templates",
        icon: FileText,
        access: "admin",
      },
    ],
  },
  {
    title: "Branding",
    items: [
      {
        name: "Portal Branding",
        href: "/branding",
        icon: Settings,
        access: "admin",
      },
    ],
  },
  {
    title: "Storage",
    items: [
      {
        name: "Storage Management",
        href: "/storage",
        icon: Database,
        access: "staff",
      },
    ],
  },
  {
    title: "Integrations",
    items: [
      {
        name: "Integrations",
        href: "/integrations",
        icon: LinkIcon,
        access: "admin",
      },
      { name: "Webhooks", href: "/webhooks", icon: LinkIcon, access: "admin" },
    ],
  },
  {
    title: "Security",
    items: [
      {
        name: "Security Overview",
        href: "/security-overview",
        icon: Shield,
        access: "staff",
      },
      {
        name: "Privacy Requests",
        href: "/privacy-requests",
        icon: EyeOff,
        access: "staff",
      },
    ],
  },
  {
    title: "Settings",
    items: [
      { name: "Profile", href: "/settings/profile", icon: Users },
      { name: "Security", href: "/settings/security", icon: Shield },
      {
        name: "Maintenance templates",
        href: "/settings/maintenance-templates",
        icon: ShieldCheck,
        access: "admin",
      },
      {
        name: "Service Templates",
        href: "/settings/service-templates",
        icon: Briefcase,
        access: "admin",
      },
      {
        name: "Form Templates",
        href: "/admin/template-forms",
        icon: FileText,
        access: "admin",
      },
      {
        name: "Email Provider",
        href: "/admin/email",
        icon: Mail,
        access: "admin",
      },
      {
        name: "Calendar",
        href: "/settings/calendar",
        icon: CalendarCheck,
        access: "staff",
      },
    ],
  },
];

const sidebarWidthClass: Record<string, string> = {
  narrow: "w-16 md:w-56",
  standard: "w-20 md:w-72",
  wide: "w-20 md:w-80",
};

const ADMIN_NAV_SECTIONS_KEY = "kre8iv-admin-nav-sections";

function isNavItemActive(
  pathname: string,
  searchParams: { get: (name: string) => string | null },
  item: NavItem,
): boolean {
  const [itemPathname, itemSearch] = item.href.split("?");
  const hrefTab = itemSearch ? new URLSearchParams(itemSearch).get("tab") : null;
  const tabFromItem = item.matchesTab ?? hrefTab;
  const currentTab = searchParams.get("tab");

  const pathnameMatch = item.exactMatch
    ? pathname === itemPathname
    : pathname === itemPathname || pathname.startsWith(`${itemPathname}/`);

  if (!pathnameMatch) {
    return false;
  }

  if (tabFromItem) {
    return currentTab === tabFromItem;
  }

  if (item.defaultWhenNoTab) {
    return !currentTab;
  }

  return pathname === itemPathname || pathname.startsWith(`${itemPathname}/`);
}

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
  const [mobileOpen, setMobileOpen] = useState(false);

  /** Admin-only: collapsed state per section (persisted). `undefined` = expanded (default). */
  const [adminSectionOpen, setAdminSectionOpen] = useState<
    Record<string, boolean>
  >({});

  useEffect(() => {
    if (!isAdmin || typeof window === "undefined") return;
    try {
      const raw = localStorage.getItem(ADMIN_NAV_SECTIONS_KEY);
      if (raw) {
        const parsed = JSON.parse(raw) as Record<string, boolean>;
        if (parsed && typeof parsed === "object") {
          setAdminSectionOpen(parsed);
        }
      }
    } catch {
      /* ignore */
    }
  }, [isAdmin]);

  const toggleAdminSection = useCallback((title: string) => {
    setAdminSectionOpen((prev) => {
      const wasOpen = prev[title] !== false;
      const next = { ...prev, [title]: !wasOpen };
      try {
        localStorage.setItem(ADMIN_NAV_SECTIONS_KEY, JSON.stringify(next));
      } catch {
        /* ignore */
      }
      return next;
    });
  }, []);

  const isAdminSectionExpanded = useCallback(
    (title: string) => adminSectionOpen[title] !== false,
    [adminSectionOpen],
  );

  const handleLogout = async () => {
    const supabase = createClient();
    await supabase.auth.signOut();
    router.push("/login");
  };

  const widthClass =
    sidebarWidthClass[sidebarWidth] ?? sidebarWidthClass.standard;

  return (
    <>
      {/* Mobile hamburger trigger — only visible on mobile */}
      <button
        className="fixed left-4 top-4 z-[60] flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background shadow-md md:hidden"
        onClick={() => setMobileOpen(true)}
        aria-label="Open navigation menu"
      >
        <Menu className="h-5 w-5" aria-hidden="true" />
      </button>

      {/* Backdrop overlay on mobile */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-[55] bg-black/50 md:hidden"
          onClick={() => setMobileOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          `flex h-dvh min-h-dvh shrink-0 flex-col border-r border-border/70 backdrop-blur ${widthClass}`,
          "md:sticky md:top-0 md:translate-x-0",
          "fixed inset-y-0 left-0 z-[58] transition-transform duration-300 ease-in-out md:bottom-auto md:transition-none",
          mobileOpen ? "translate-x-0" : "-translate-x-full md:translate-x-0",
        )}
        style={{
          backgroundColor: "var(--sidebar-bg)",
          color: "var(--sidebar-text)",
        }}
        aria-label="Main navigation"
      >
        {/* Close button inside sidebar — mobile only */}
        <button
          className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg md:hidden"
          style={{
            backgroundColor: "rgba(255,255,255,0.1)",
            color: "var(--sidebar-text)",
          }}
          onClick={() => setMobileOpen(false)}
          aria-label="Close navigation menu"
        >
          <X className="h-4 w-4" aria-hidden="true" />
        </button>

        <div className="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white/5 to-transparent" />
        {/* Logo */}
        <div
          className="relative flex h-20 items-center px-6"
          style={{ borderBottom: "1px solid rgba(255,255,255,0.12)" }}
        >
          {logoUrl ? (
            <img
              src={logoUrl}
              alt="Company logo"
              className="h-11 w-11 rounded-xl object-contain p-1"
              style={{
                border: "1px solid rgba(255,255,255,0.2)",
                backgroundColor: "rgba(255,255,255,0.1)",
              }}
            />
          ) : (
            <div
              className="flex h-11 w-11 items-center justify-center rounded-xl text-lg font-bold"
              style={{
                border: "1px solid rgba(255,255,255,0.2)",
                backgroundColor: "rgba(255,255,255,0.15)",
                color: "var(--sidebar-text)",
              }}
            >
              {brandText ? brandText.slice(0, 1).toUpperCase() : "K"}
            </div>
          )}
          <div className="ml-3 hidden md:block">
            <h1
              className="text-lg font-bold font-heading leading-none"
              style={{ color: "var(--sidebar-text)" }}
            >
              {brandText || "KRE8IV"}
            </h1>
            <p
              className="mt-1 text-xs uppercase tracking-[0.18em]"
              style={{ color: "var(--sidebar-text)", opacity: 0.55 }}
            >
              {siteTitle}
            </p>
          </div>
        </div>

        {/* Navigation */}
        <nav className="relative flex-1 space-y-6 overflow-y-auto px-4 py-5">
          <Link href="/dashboard">
            <Button
              variant="ghost"
              aria-current={pathname === "/dashboard" ? "page" : undefined}
              className={cn(
                "h-11 w-full justify-center rounded-xl px-3.5 text-[0.95rem] md:justify-start",
                "hover:bg-white/10 hover:text-[var(--sidebar-text)]",
                pathname === "/dashboard"
                  ? "bg-white/15 font-semibold"
                  : "font-normal opacity-90",
              )}
              style={{ color: "var(--sidebar-text)" }}
            >
              <LayoutDashboard className="h-4 w-4 md:mr-3" aria-hidden="true" />
              <span className="hidden md:inline">Dashboard</span>
            </Button>
          </Link>

          {navigationSections.map((section) => {
            const visibleItems = section.items.filter((item) =>
              canAccessItem(item.access, isStaff, isAdmin, isAccountManager),
            );
            if (visibleItems.length === 0) return null;

            const sectionExpanded =
              !isAdmin || isAdminSectionExpanded(section.title);

            return (
              <div key={section.title}>
                {isAdmin ? (
                  <button
                    type="button"
                    onClick={() => toggleAdminSection(section.title)}
                    className="mb-2 flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1 text-left transition hover:bg-white/10 md:px-2"
                    style={{ color: "var(--sidebar-text)" }}
                    aria-expanded={sectionExpanded}
                  >
                    <span
                      className="text-xs font-semibold uppercase tracking-[0.14em]"
                      style={{ opacity: 0.85 }}
                    >
                      {section.title}
                    </span>
                    <ChevronDown
                      className={cn(
                        "h-4 w-4 shrink-0 opacity-80 transition-transform duration-200",
                        sectionExpanded ? "rotate-0" : "-rotate-90",
                      )}
                      aria-hidden="true"
                    />
                  </button>
                ) : (
                  <p
                    className="mb-2 hidden px-2 text-xs font-semibold uppercase tracking-[0.14em] md:block"
                    style={{ color: "var(--sidebar-text)", opacity: 0.7 }}
                  >
                    {section.title}
                  </p>
                )}
                <div
                  className={cn(
                    "space-y-1.5",
                    isAdmin && !sectionExpanded && "hidden",
                  )}
                >
                  {visibleItems.map((item) => {
                    const isActive = isNavItemActive(
                      pathname,
                      searchParams,
                      item,
                    );
                    return (
                      <Link key={item.name} href={item.href}>
                        <Button
                          variant="ghost"
                          aria-current={isActive ? "page" : undefined}
                          className={cn(
                            "h-10 w-full justify-center rounded-xl px-3 text-[0.93rem] md:justify-start",
                            "hover:bg-white/10 hover:text-[var(--sidebar-text)]",
                            isActive
                              ? "bg-white/15 font-semibold"
                              : "font-normal opacity-85",
                          )}
                          style={{ color: "var(--sidebar-text)" }}
                        >
                          <item.icon
                            className="h-4 w-4 md:mr-3 shrink-0"
                            aria-hidden="true"
                          />
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
        <div
          className="relative border-t p-3 shadow-[0_-16px_32px_rgba(15,23,42,0.08)] md:p-4"
          style={{ borderColor: "rgba(255,255,255,0.12)" }}
        >
          <div
            className="mb-3 hidden items-center gap-3 rounded-xl border p-2.5 shadow-sm md:flex"
            style={{
              borderColor: "rgba(255,255,255,0.12)",
              backgroundColor: "rgba(255,255,255,0.08)",
            }}
          >
            <Avatar>
              <AvatarImage src={user.user_metadata?.avatar} />
              <AvatarFallback
                style={{
                  backgroundColor: "rgba(255,255,255,0.15)",
                  color: "var(--sidebar-text)",
                }}
              >
                {user.user_metadata?.name
                  ?.split(" ")
                  .map((n) => n[0])
                  .join("") || "U"}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 overflow-hidden">
              <p
                className="truncate text-sm font-medium"
                style={{ color: "var(--sidebar-text)" }}
              >
                {user.user_metadata?.name || "User"}
              </p>
              <p
                className="truncate text-xs"
                style={{ color: "var(--sidebar-text)", opacity: 0.75 }}
              >
                {user.email}
              </p>
            </div>
          </div>
          <Button
            variant="ghost"
            size="sm"
            aria-label="Sign out"
            className="h-10 w-full justify-center rounded-xl bg-white/10 text-sm font-semibold transition hover:bg-white/15 hover:shadow-sm md:justify-start"
            style={{
              color: "var(--sidebar-text)",
              border: "1px solid rgba(255,255,255,0.18)",
            }}
            onClick={handleLogout}
          >
            <LogOut className="h-4 w-4 md:mr-2" aria-hidden="true" />
            <span className="hidden md:inline">Logout</span>
          </Button>
        </div>
      </aside>
    </>
  );
}
