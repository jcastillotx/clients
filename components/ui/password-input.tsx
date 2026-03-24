"use client";

import * as React from "react";
import { Sparkles } from "lucide-react";
import { cn } from "@/lib/utils";
import { generateSecurePassword } from "@/lib/utils/generate-password";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export type PasswordInputProps = Omit<React.ComponentProps<typeof Input>, "type"> & {
  /** Show generate button (requires onGeneratePassword). Default true. */
  showGenerate?: boolean;
  /** Called with a new secure password when the user clicks Generate. */
  onGeneratePassword?: (password: string) => void;
};

const PasswordInput = React.forwardRef<HTMLInputElement, PasswordInputProps>(
  ({ className, showGenerate = true, onGeneratePassword, disabled, ...props }, ref) => {
    const showBtn = showGenerate && typeof onGeneratePassword === "function";

    const handleGenerate = () => {
      if (!onGeneratePassword) return;
      onGeneratePassword(generateSecurePassword());
    };

    return (
      <div className="flex gap-2">
        <Input
          ref={ref}
          type="password"
          className={cn("min-w-0 flex-1", className)}
          disabled={disabled}
          autoComplete={props.autoComplete ?? "new-password"}
          {...props}
        />
        {showBtn ? (
          <Button
            type="button"
            variant="outline"
            size="icon"
            className="h-10 w-10 shrink-0"
            disabled={disabled}
            onClick={handleGenerate}
            aria-label="Generate secure password"
            title="Generate secure password"
          >
            <Sparkles className="h-4 w-4" aria-hidden />
          </Button>
        ) : null}
      </div>
    );
  },
);
PasswordInput.displayName = "PasswordInput";

export { PasswordInput };
