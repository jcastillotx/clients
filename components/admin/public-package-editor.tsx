"use client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { packageGroups, type PublicPackage } from "@/lib/catalog/schema";

export function PublicPackageEditor({
  value,
  onChange,
}: {
  value: PublicPackage[];
  onChange: (value: PublicPackage[]) => void;
}) {
  const update = (index: number, patch: Partial<PublicPackage>) =>
    onChange(
      value.map((item, i) => (i === index ? { ...item, ...patch } : item)),
    );
  return (
    <section className="space-y-4 border-t pt-4">
      <div>
        <h3 className="font-semibold">Public website packages</h3>
        <p className="text-sm text-muted-foreground">
          Published packages appear on kre8ivdesigns.com. Scope is shared;
          prices, internal notes and client records stay private. Unpublish a
          package to remove it from the website.
        </p>
      </div>
      {value.map((item, index) => (
        <div className="space-y-3 rounded-lg border p-4" key={item.id}>
          <div>
            <Label htmlFor={`package-name-${item.id}`}>Package name</Label>
            <Input
              id={`package-name-${item.id}`}
              value={item.name}
              maxLength={120}
              onChange={(e) => update(index, { name: e.target.value })}
            />
          </div>
          <div>
            <Label htmlFor={`package-group-${item.id}`}>Package category</Label>
            <select
              className="flex h-10 w-full rounded-md border bg-background px-3"
              id={`package-group-${item.id}`}
              value={item.group}
              onChange={(e) =>
                update(index, {
                  group: e.target.value as PublicPackage["group"],
                })
              }
            >
              {packageGroups.map((group) => (
                <option key={group}>{group}</option>
              ))}
            </select>
          </div>
          <div>
            <Label htmlFor={`package-fit-${item.id}`}>Who this helps</Label>
            <Textarea
              id={`package-fit-${item.id}`}
              value={item.fit}
              maxLength={1000}
              onChange={(e) => update(index, { fit: e.target.value })}
            />
          </div>
          <div>
            <Label htmlFor={`package-includes-${item.id}`}>
              Included work — one item per line
            </Label>
            <Textarea
              id={`package-includes-${item.id}`}
              value={item.includes.join("\n")}
              onChange={(e) =>
                update(index, { includes: e.target.value.split("\n") })
              }
            />
          </div>
          <div className="flex items-center justify-between">
            <Label htmlFor={`package-published-${item.id}`}>
              Published on the website
            </Label>
            <Switch
              id={`package-published-${item.id}`}
              checked={item.published}
              onCheckedChange={(published) => update(index, { published })}
            />
          </div>
        </div>
      ))}
      <Button
        type="button"
        variant="outline"
        onClick={() =>
          onChange([
            ...value,
            {
              id: crypto.randomUUID(),
              name: "",
              group: "Website projects",
              fit: "",
              includes: [""],
              published: false,
            },
          ])
        }
      >
        Add public package
      </Button>
    </section>
  );
}
