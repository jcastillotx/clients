"use client";

import { Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card";

interface Font {
  id: string;
  family: string;
  usage: string;
  weight: string;
  provider: "google" | "custom" | "system";
}

interface BrandFontSelectorProps {
  fonts: Font[];
  onChange: (fonts: Font[]) => void;
}

export function BrandFontSelector({ fonts, onChange }: BrandFontSelectorProps) {
  const addFont = () => {
    const newFont: Font = {
      id: Math.random().toString(36).substr(2, 9),
      family: "Inter",
      usage: "Body",
      weight: "400",
      provider: "google",
    };
    onChange([...fonts, newFont]);
  };

  const removeFont = (id: string) => {
    onChange(fonts.filter((f) => f.id !== id));
  };

  const updateFont = (id: string, updates: Partial<Font>) => {
    onChange(fonts.map((f) => (f.id === id ? { ...f, ...updates } : f)));
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium">Typography</h3>
        <Button onClick={addFont} variant="outline" size="sm">
          <Plus className="mr-2 h-4 w-4" />
          Add Font
        </Button>
      </div>

      <div className="grid gap-4">
        {fonts.map((font) => (
          <Card key={font.id}>
            <CardContent className="p-4">
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div className="space-y-1">
                  <Label className="text-xs">Font Family</Label>
                  <Input
                    value={font.family}
                    onChange={(e) => updateFont(font.id, { family: e.target.value })}
                    placeholder="e.g. Inter, Roboto"
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Usage</Label>
                  <Input
                    value={font.usage}
                    onChange={(e) => updateFont(font.id, { usage: e.target.value })}
                    placeholder="e.g. Headings, Body"
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Weight</Label>
                  <Input
                    value={font.weight}
                    onChange={(e) => updateFont(font.id, { weight: e.target.value })}
                    placeholder="e.g. 400, 700"
                  />
                </div>
                <div className="flex items-center gap-2">
                  <div className="space-y-1 flex-1">
                    <Label className="text-xs">Provider</Label>
                    <select
                      className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      value={font.provider}
                      onChange={(e) => updateFont(font.id, { provider: e.target.value as any })}
                    >
                      <option value="google">Google Fonts</option>
                      <option value="custom">Custom</option>
                      <option value="system">System</option>
                    </select>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => removeFont(font.id)}
                    className="text-destructive mt-5"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
              <div className="mt-4 p-4 border rounded bg-muted/30">
                <p style={{ fontFamily: font.family, fontWeight: font.weight }}>
                  The quick brown fox jumps over the lazy dog (Preview)
                </p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {fonts.length === 0 && (
        <div className="text-center py-10 border-2 border-dashed rounded-lg bg-muted/50">
          <p className="text-sm text-muted-foreground">No fonts added yet.</p>
          <Button onClick={addFont} variant="link" className="mt-2 text-primary">
            Click to add your first font
          </Button>
        </div>
      )}
    </div>
  );
}
