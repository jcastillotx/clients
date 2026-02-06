"use client";

import { useState } from "react";
import { Plus, Trash2, GripVertical } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card";

interface Color {
  id: string;
  name: string;
  value: string;
  type: string;
}

interface BrandColorPickerProps {
  colors: Color[];
  onChange: (colors: Color[]) => void;
}

export function BrandColorPicker({ colors, onChange }: BrandColorPickerProps) {
  const addColor = () => {
    const newColor: Color = {
      id: Math.random().toString(36).substr(2, 9),
      name: "New Color",
      value: "#000000",
      type: "primary",
    };
    onChange([...colors, newColor]);
  };

  const removeColor = (id: string) => {
    onChange(colors.filter((c) => c.id !== id));
  };

  const updateColor = (id: string, updates: Partial<Color>) => {
    onChange(colors.map((c) => (c.id === id ? { ...c, ...updates } : c)));
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium">Brand Colors</h3>
        <Button onClick={addColor} variant="outline" size="sm">
          <Plus className="mr-2 h-4 w-4" />
          Add Color
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {colors.map((color) => (
          <Card key={color.id}>
            <CardContent className="p-4 space-y-3">
              <div className="flex items-center gap-2">
                <div
                  className="w-10 h-10 rounded border shadow-sm"
                  style={{ backgroundColor: color.value }}
                />
                <Input
                  value={color.name}
                  onChange={(e) => updateColor(color.id, { name: e.target.value })}
                  className="h-8"
                  placeholder="Color Name"
                />
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => removeColor(color.id)}
                  className="h-8 w-8 text-destructive"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1">
                  <Label className="text-xs">Hex Value</Label>
                  <Input
                    type="color"
                    value={color.value}
                    onChange={(e) => updateColor(color.id, { value: e.target.value })}
                    className="h-8 p-1"
                  />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Type</Label>
                  <select
                    className="flex h-8 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    value={color.type}
                    onChange={(e) => updateColor(color.id, { type: e.target.value })}
                  >
                    <option value="primary">Primary</option>
                    <option value="secondary">Secondary</option>
                    <option value="accent">Accent</option>
                    <option value="neutral">Neutral</option>
                  </select>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {colors.length === 0 && (
        <div className="text-center py-10 border-2 border-dashed rounded-lg bg-muted/50">
          <p className="text-sm text-muted-foreground">No colors added yet.</p>
          <Button onClick={addColor} variant="link" className="mt-2 text-primary">
            Click to add your first color
          </Button>
        </div>
      )}
    </div>
  );
}
