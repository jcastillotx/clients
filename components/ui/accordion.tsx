"use client";

import * as React from "react";
import { ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";

// ---------------------------------------------------------------------------
// Lightweight accordion built on native <details>/<summary>.
// Matches the shadcn/ui API surface used in the codebase so it can be
// swapped for @radix-ui/react-accordion later without changing call sites.
// ---------------------------------------------------------------------------

interface AccordionProps {
  type?: "single" | "multiple";
  collapsible?: boolean;
  className?: string;
  children: React.ReactNode;
}

const Accordion = React.forwardRef<HTMLDivElement, AccordionProps>(
  ({ className, children, ...props }, ref) => (
    <div ref={ref} className={cn("divide-y divide-border", className)} {...props}>
      {children}
    </div>
  ),
);
Accordion.displayName = "Accordion";

interface AccordionItemProps {
  value: string;
  className?: string;
  children: React.ReactNode;
}

const AccordionItem = React.forwardRef<HTMLDetailsElement, AccordionItemProps>(
  ({ className, children, value: _value, ...props }, ref) => (
    <details
      ref={ref}
      className={cn("group py-0", className)}
      {...props}
    >
      {children}
    </details>
  ),
);
AccordionItem.displayName = "AccordionItem";

interface AccordionTriggerProps extends React.HTMLAttributes<HTMLElement> {
  className?: string;
  children: React.ReactNode;
}

const AccordionTrigger = React.forwardRef<HTMLElement, AccordionTriggerProps>(
  ({ className, children, ...props }, ref) => (
    <summary
      ref={ref as React.Ref<HTMLElement>}
      className={cn(
        "flex cursor-pointer list-none items-center justify-between py-4 text-sm font-medium transition-all hover:underline [&::-webkit-details-marker]:hidden",
        className,
      )}
      {...props}
    >
      {children}
      <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-180" />
    </summary>
  ),
);
AccordionTrigger.displayName = "AccordionTrigger";

interface AccordionContentProps {
  className?: string;
  children: React.ReactNode;
}

const AccordionContent = React.forwardRef<HTMLDivElement, AccordionContentProps>(
  ({ className, children, ...props }, ref) => (
    <div
      ref={ref}
      className={cn("pb-4 pt-0 text-sm", className)}
      {...props}
    >
      {children}
    </div>
  ),
);
AccordionContent.displayName = "AccordionContent";

export { Accordion, AccordionItem, AccordionTrigger, AccordionContent };
