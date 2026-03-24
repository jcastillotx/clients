"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { PasswordInput } from "@/components/ui/password-input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { BrandColorPicker } from "./brand-color-picker";
import { BrandFontSelector } from "./brand-font-selector";
import { BrandAssetUploader } from "./brand-asset-uploader";
import { toast } from "sonner";
import { Loader2, Save, Eye, Upload } from "lucide-react";

const brandGuideSchema = z.object({
  clientId: z.string().uuid().optional(),
  slug: z.string().min(1, "Slug is required"),
  status: z.enum(["draft", "published"]),
  coverImage: z.string().optional(),
  isPublic: z.boolean().default(false),
  passwordProtected: z.boolean().default(false),
  password: z.string().optional(),
  version: z.number().default(1),
});

type BrandGuideFormValues = z.infer<typeof brandGuideSchema>;

export function BrandGuideBuilder() {
  const [isLoading, setIsLoading] = useState(false);
  const [activeTab, setActiveTab] = useState("basics");
  const [colors, setColors] = useState<any[]>([]);
  const [fonts, setFonts] = useState<any[]>([]);
  const [sections, setSections] = useState<any[]>([]);

  const form = useForm<BrandGuideFormValues>({
    resolver: zodResolver(brandGuideSchema),
    defaultValues: {
      status: "draft",
      isPublic: false,
      passwordProtected: false,
      version: 1,
    },
  });

  const onSubmit = async (data: BrandGuideFormValues) => {
    setIsLoading(true);
    try {
      const response = await fetch("/api/brand/guides", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...data,
          colors,
          fonts,
          sections,
        }),
      });

      if (!response.ok) throw new Error("Failed to create brand guide");

      const result = await response.json();
      toast.success("Brand guide created successfully");
      form.reset();
    } catch (error) {
      toast.error("Failed to create brand guide");
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Create Brand Guide</CardTitle>
        <CardDescription>Build comprehensive brand guidelines for your clients</CardDescription>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <Tabs value={activeTab} onValueChange={setActiveTab}>
              <TabsList className="grid w-full grid-cols-5">
                <TabsTrigger value="basics">Basics</TabsTrigger>
                <TabsTrigger value="colors">Colors</TabsTrigger>
                <TabsTrigger value="typography">Typography</TabsTrigger>
                <TabsTrigger value="assets">Assets</TabsTrigger>
                <TabsTrigger value="settings">Settings</TabsTrigger>
              </TabsList>

              <TabsContent value="basics" className="space-y-4">
                <FormField
                  control={form.control}
                  name="slug"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Slug</FormLabel>
                      <FormControl>
                        <Input placeholder="brand-guide-2024" {...field} />
                      </FormControl>
                      <FormDescription>Unique identifier for the brand guide</FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="coverImage"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Cover Image</FormLabel>
                      <FormControl>
                        <div className="flex gap-2">
                          <Input placeholder="https://..." {...field} />
                          <Button type="button" variant="outline" size="icon">
                            <Upload className="h-4 w-4" />
                          </Button>
                        </div>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="status"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Status</FormLabel>
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="draft">Draft</SelectItem>
                          <SelectItem value="published">Published</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </TabsContent>

              <TabsContent value="colors" className="space-y-4">
                <BrandColorPicker colors={colors} onChange={setColors} />
              </TabsContent>

              <TabsContent value="typography" className="space-y-4">
                <BrandFontSelector fonts={fonts} onChange={setFonts} />
              </TabsContent>

              <TabsContent value="assets" className="space-y-4">
                <BrandAssetUploader />
              </TabsContent>

              <TabsContent value="settings" className="space-y-4">
                <FormField
                  control={form.control}
                  name="isPublic"
                  render={({ field }) => (
                    <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                      <div className="space-y-0.5">
                        <FormLabel className="text-base">Public Access</FormLabel>
                        <FormDescription>Allow anyone with the link to view this guide</FormDescription>
                      </div>
                      <FormControl>
                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                      </FormControl>
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="passwordProtected"
                  render={({ field }) => (
                    <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                      <div className="space-y-0.5">
                        <FormLabel className="text-base">Password Protection</FormLabel>
                        <FormDescription>Require a password to access this guide</FormDescription>
                      </div>
                      <FormControl>
                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                      </FormControl>
                    </FormItem>
                  )}
                />

                {form.watch("passwordProtected") && (
                  <FormField
                    control={form.control}
                    name="password"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Password</FormLabel>
                        <FormControl>
                          <PasswordInput
                            placeholder="Enter password"
                            name={field.name}
                            ref={field.ref}
                            value={field.value ?? ""}
                            onBlur={field.onBlur}
                            onChange={(e) => field.onChange(e.target.value)}
                            onGeneratePassword={(pw) => field.onChange(pw)}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                )}
              </TabsContent>
            </Tabs>

            <div className="flex gap-2">
              <Button type="submit" disabled={isLoading}>
                {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                Save Brand Guide
              </Button>
              <Button type="button" variant="outline">
                <Eye className="mr-2 h-4 w-4" />
                Preview
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}
