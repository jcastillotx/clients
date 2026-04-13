"use client";

import { useChat } from "ai/react";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { MessageCircle, X, Loader2, Send, Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";

const BUBBLE_GRADIENT =
  "bg-gradient-to-br from-[#0f172a] via-[#4c1d95] to-[#7c2d12] shadow-lg shadow-violet-950/40";

const KRE8IV_LOGO =
  "https://www.kre8ivdesigns.com/wp-content/uploads/2020/02/logo-copy-2-300x57.png";

export function Kre8ivHelpBubble() {
  const [open, setOpen] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);
  const { messages, input, handleInputChange, handleSubmit, isLoading, error, reload } = useChat({
    api: "/api/ai/help",
    id: "kre8iv-help-bubble",
  });

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages, isLoading, open]);

  return (
    <div className="pointer-events-none fixed bottom-0 right-0 z-[60] flex flex-col items-end p-4 md:p-6">
      {open && (
        <div
          id="kre8iv-help-panel"
          className="pointer-events-auto mb-3 flex max-h-[min(520px,calc(100vh-7rem))] w-[min(100vw-2rem,380px)] flex-col overflow-hidden rounded-2xl border border-white/10 bg-background/95 shadow-2xl backdrop-blur-md"
        >
          <div
            className={cn(
              "flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3 text-white",
              BUBBLE_GRADIENT,
            )}
          >
            <div className="relative h-8 w-[140px] shrink-0">
              <Image
                src={KRE8IV_LOGO}
                alt="Kre8ivDesigns"
                width={140}
                height={27}
                className="h-7 w-auto object-contain object-left"
                sizes="140px"
              />
            </div>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="h-8 w-8 shrink-0 text-white hover:bg-white/10"
              onClick={() => setOpen(false)}
              aria-label="Close help"
            >
              <X className="h-4 w-4" />
            </Button>
          </div>

          <div className="flex min-h-0 flex-1 flex-col bg-background">
            <div
              ref={scrollRef}
              className="h-[280px] overflow-y-auto px-3 py-3"
            >
              <div className="space-y-3 pr-1">
                {messages.length === 0 && (
                  <div className="rounded-lg border border-dashed border-muted-foreground/25 bg-muted/40 px-3 py-4 text-center text-sm text-muted-foreground">
                    <Sparkles className="mx-auto mb-2 h-6 w-6 text-amber-600/80" />
                    <p className="font-medium text-foreground">How can we help?</p>
                    <p className="mt-1 text-xs leading-relaxed">
                      Ask where to find features, how workflows work, or basic how-tos. This helper keeps
                      replies brief to save time and usage.
                    </p>
                  </div>
                )}

                {messages.map((m) => (
                  <div
                    key={m.id}
                    className={cn(
                      "flex",
                      m.role === "user" ? "justify-end" : "justify-start",
                    )}
                  >
                    <div
                      className={cn(
                        "max-w-[92%] rounded-xl px-3 py-2 text-sm leading-relaxed",
                        m.role === "user"
                          ? "bg-primary text-primary-foreground"
                          : "bg-muted text-foreground",
                      )}
                    >
                      <p className="whitespace-pre-wrap">{m.content}</p>
                    </div>
                  </div>
                ))}

                {isLoading && (
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    <span className="text-xs">Thinking…</span>
                  </div>
                )}

                {error && (
                  <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs text-destructive">
                    {String(error.message || "").includes("429")
                      ? "Daily limit reached for quick help. Try again tomorrow."
                      : "Something went wrong. Try again."}{" "}
                    <button
                      type="button"
                      className="font-medium underline underline-offset-2"
                      onClick={() => reload()}
                    >
                      Retry
                    </button>
                  </div>
                )}
              </div>
            </div>

            <form
              onSubmit={handleSubmit}
              className="border-t border-border p-3"
            >
              <div className="flex gap-2">
                <Textarea
                  value={input}
                  onChange={handleInputChange}
                  placeholder="e.g. Where do I upload a document?"
                  className="min-h-[44px] resize-none text-sm"
                  disabled={isLoading}
                  maxLength={900}
                  onKeyDown={(e) => {
                    if (e.key === "Enter" && !e.shiftKey) {
                      e.preventDefault();
                      handleSubmit(e as unknown as React.FormEvent<HTMLFormElement>);
                    }
                  }}
                />
                <Button
                  type="submit"
                  size="icon"
                  className="shrink-0"
                  disabled={isLoading || !input.trim()}
                  aria-label="Send"
                >
                  <Send className="h-4 w-4" />
                </Button>
              </div>
              <p className="mt-2 text-[10px] leading-snug text-muted-foreground">
                Quick help only — not a general AI. For{" "}
                <Link
                  href="https://www.kre8ivdesigns.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-medium text-primary underline-offset-2 hover:underline"
                >
                  Kre8ivDesigns
                </Link>{" "}
                marketing services, visit their site.
              </p>
            </form>
          </div>
        </div>
      )}

      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className={cn(
          "pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full text-white transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:ring-offset-background",
          BUBBLE_GRADIENT,
        )}
        aria-expanded={open}
        aria-controls="kre8iv-help-panel"
        aria-label={open ? "Close Kre8iv help" : "Open Kre8iv help"}
      >
        {open ? <X className="h-6 w-6" /> : <MessageCircle className="h-7 w-7" />}
      </button>
    </div>
  );
}
