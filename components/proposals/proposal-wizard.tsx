"use client";

import { useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { useForm, useFieldArray, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Separator } from "@/components/ui/separator";
import { Progress } from "@/components/ui/progress";
import { createClient } from "@/lib/supabase/client";
import { Loader2, Plus, Trash2, ChevronLeft, ChevronRight, FileText, Sparkles } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

const proposalSchema = z.object({
  clientId: z.string().uuid("Please select a client"),
  title: z.string().min(1, "Title is required"),
  description: z.string().optional(),
  validUntil: z.string().optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  terms: z.string().optional(),
  lineItems: z
    .array(
      z.object({
        description: z.string().min(1, "Description is required"),
        quantity: z.coerce.number().min(0.01, "Quantity must be positive"),
        unitPrice: z.coerce.number().min(0, "Unit price must be positive"),
        category: z.string().optional(),
      }),
    )
    .min(1, "At least one line item is required"),
  metadata: z
    .object({
      notes: z.string().optional(),
      internalNotes: z.string().optional(),
      tags: z.array(z.string()).optional(),
    })
    .optional(),
});

type ProposalFormInput = z.infer<typeof proposalSchema>;

interface ProposalWizardProps {
  clients: Array<{
    id: string;
    company_name: string;
    email: string;
  }>;
  preselectedClientId?: string;
}

const steps = [
  { id: 1, name: "Basic Info", description: "Client and proposal details" },
  { id: 2, name: "Line Items", description: "Add services and pricing" },
  { id: 3, name: "AI Generate", description: "Generate detailed proposal" },
  { id: 4, name: "Terms", description: "Terms and conditions" },
  { id: 5, name: "Review", description: "Review and submit" },
];

export function ProposalWizard({ clients, preselectedClientId }: ProposalWizardProps) {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [generatedContent, setGeneratedContent] = useState<string>("");
  const [aiContext, setAiContext] = useState("");
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    control,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<ProposalFormInput>({
    resolver: zodResolver(proposalSchema),
    defaultValues: {
      clientId: preselectedClientId || "",
      currency: "USD",
      lineItems: [{ description: "", quantity: 1, unitPrice: 0, category: "" }],
      metadata: {
        notes: "",
        internalNotes: "",
        tags: [],
      },
    },
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: "lineItems",
  });

  const lineItems = watch("lineItems");
  const totalAmount = lineItems.reduce(
    (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unitPrice) || 0),
    0,
  );

  const generateProposal = useCallback(async () => {
    const clientId = watch("clientId");
    const title = watch("title");
    const description = watch("description");
    const currency = watch("currency");
    const items = watch("lineItems");

    if (!clientId || !title) {
      setError("Please fill in the client and title before generating.");
      return;
    }

    const selectedClient = clients.find((c) => c.id === clientId);
    if (!selectedClient) {
      setError("Please select a valid client.");
      return;
    }

    setIsGenerating(true);
    setError(null);

    try {
      const data = await fetchApi<{ content: string }>(
        "/api/ai/generate-proposal",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            clientName: selectedClient.company_name,
            projectTitle: title,
            projectDescription: description || "",
            lineItems: items
              .filter((item) => item.description)
              .map((item) => ({
                description: item.description,
                quantity: Number(item.quantity) || 1,
                unitPrice: Number(item.unitPrice) || 0,
                category: item.category || "",
              })),
            currency,
            additionalContext: aiContext || undefined,
          }),
        },
        { fallbackMessage: "Failed to generate proposal" },
      );
      setGeneratedContent(data.content);

      // Auto-populate the description if empty
      if (!description) {
        setValue("description", `AI-generated detailed proposal for ${title}`);
      }
    } catch (err) {
      console.error("Error generating proposal:", err);
      setError(err instanceof Error ? err.message : "Failed to generate proposal");
    } finally {
      setIsGenerating(false);
    }
  }, [watch, clients, aiContext, setValue]);

  const onSubmit = async (data: ProposalFormInput) => {
    setIsSubmitting(true);
    setError(null);

    try {
      const supabase = createClient();
      const {
        data: { user },
      } = await supabase.auth.getUser();

      if (!user) {
        throw new Error("You must be logged in to create a proposal");
      }

      // Calculate total and prepare line items
      const lineItemsWithAmounts = data.lineItems.map((item) => ({
        id: crypto.randomUUID(),
        description: item.description,
        quantity: Number(item.quantity),
        unitPrice: Number(item.unitPrice),
        amount: Number(item.quantity) * Number(item.unitPrice),
        category: item.category || undefined,
      }));

      const total = lineItemsWithAmounts.reduce((sum, item) => sum + item.amount, 0);

      // Create proposal
      const { data: proposal, error: insertError } = await supabase
        .from("proposals")
        .insert({
          client_id: data.clientId,
          title: data.title,
          description: data.description || null,
          status: "draft",
          total_amount: total.toString(),
          currency: data.currency,
          valid_until: data.validUntil || null,
          terms: data.terms || null,
          line_items: lineItemsWithAmounts,
          metadata: {
            ...data.metadata,
            generatedContent: generatedContent || undefined,
          },
          created_by: user.id,
        })
        .select()
        .single();

      if (insertError) throw insertError;

      router.push(`/proposals/${proposal.id}`);
    } catch (err) {
      console.error("Error creating proposal:", err);
      setError(err instanceof Error ? err.message : "Failed to create proposal");
    } finally {
      setIsSubmitting(false);
    }
  };

  const nextStep = () => setCurrentStep((prev) => Math.min(prev + 1, steps.length));
  const prevStep = () => setCurrentStep((prev) => Math.max(prev - 1, 1));

  const progressPercentage = (currentStep / steps.length) * 100;

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* Progress Indicator */}
      <Card>
        <CardContent className="pt-6">
          <div className="space-y-4">
            <div className="flex justify-between mb-2">
              {steps.map((step) => (
                <div
                  key={step.id}
                  className={`flex flex-col items-center ${
                    step.id === currentStep ? "text-primary" : "text-muted-foreground"
                  }`}
                >
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                      step.id === currentStep
                        ? "bg-primary text-primary-foreground"
                        : step.id < currentStep
                          ? "bg-green-500 text-white"
                          : "bg-muted"
                    }`}
                  >
                    {step.id < currentStep ? "✓" : step.id}
                  </div>
                  <div className="text-sm font-medium">{step.name}</div>
                  <div className="text-xs text-muted-foreground">{step.description}</div>
                </div>
              ))}
            </div>
            <Progress value={progressPercentage} />
          </div>
        </CardContent>
      </Card>

      {error && <div className="bg-destructive/15 text-destructive px-4 py-3 rounded-lg">{error}</div>}

      {/* Step 1: Basic Info */}
      {currentStep === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
            <CardDescription>Enter the proposal details and select a client</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="clientId">Client *</Label>
              <Controller
                control={control}
                name="clientId"
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger id="clientId">
                      <SelectValue placeholder="Select a client" />
                    </SelectTrigger>
                    <SelectContent>
                      {clients.map((client) => (
                        <SelectItem key={client.id} value={client.id}>
                          {client.company_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
              {errors.clientId && <p className="text-sm text-destructive">{errors.clientId.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="title">Proposal Title *</Label>
              <Input id="title" {...register("title")} placeholder="e.g., Website Redesign Proposal" />
              {errors.title && <p className="text-sm text-destructive">{errors.title.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                {...register("description")}
                placeholder="Brief overview of the proposal..."
                rows={4}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="validUntil">Valid Until</Label>
                <Input id="validUntil" type="date" {...register("validUntil")} />
              </div>

              <div className="space-y-2">
                <Label htmlFor="currency">Currency</Label>
                <Controller
                  control={control}
                  name="currency"
                  render={({ field }) => (
                    <Select value={field.value} onValueChange={field.onChange}>
                      <SelectTrigger id="currency">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="USD">USD ($)</SelectItem>
                        <SelectItem value="EUR">EUR (€)</SelectItem>
                        <SelectItem value="GBP">GBP (£)</SelectItem>
                        <SelectItem value="CAD">CAD (C$)</SelectItem>
                      </SelectContent>
                    </Select>
                  )}
                />
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 2: Line Items */}
      {currentStep === 2 && (
        <Card>
          <CardHeader>
            <CardTitle>Line Items</CardTitle>
            <CardDescription>Add services, products, or deliverables</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Description</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead className="w-24">Quantity</TableHead>
                    <TableHead className="w-32">Unit Price</TableHead>
                    <TableHead className="w-32">Amount</TableHead>
                    <TableHead className="w-16"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {fields.map((field, index) => (
                    <TableRow key={field.id}>
                      <TableCell>
                        <Input {...register(`lineItems.${index}.description`)} placeholder="Service description" />
                        {errors.lineItems?.[index]?.description && (
                          <p className="text-xs text-destructive mt-1">
                            {errors.lineItems[index]?.description?.message}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Input {...register(`lineItems.${index}.category`)} placeholder="Optional" />
                      </TableCell>
                      <TableCell>
                        <Input type="number" step="0.01" {...register(`lineItems.${index}.quantity`)} />
                      </TableCell>
                      <TableCell>
                        <Input type="number" step="0.01" {...register(`lineItems.${index}.unitPrice`)} />
                      </TableCell>
                      <TableCell>
                        $
                        {(
                          (Number(lineItems[index]?.quantity) || 0) * (Number(lineItems[index]?.unitPrice) || 0)
                        ).toFixed(2)}
                      </TableCell>
                      <TableCell>
                        {fields.length > 1 && (
                          <Button type="button" variant="ghost" size="sm" onClick={() => remove(index)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>

            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => append({ description: "", quantity: 1, unitPrice: 0, category: "" })}
            >
              <Plus className="mr-2 h-4 w-4" />
              Add Item
            </Button>

            <Separator />

            <div className="flex justify-end">
              <div className="space-y-2 w-64">
                <div className="flex justify-between text-lg font-semibold">
                  <span>Total:</span>
                  <span>${totalAmount.toFixed(2)}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 3: AI Generate */}
      {currentStep === 3 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Sparkles className="h-5 w-5 text-violet-500" />
              AI Proposal Generator
            </CardTitle>
            <CardDescription>
              Use Claude AI to generate a comprehensive, professionally detailed proposal with full legal sections
              including Parties & Recitals, Scope of Work, Payment Terms, IP, Confidentiality, Warranties, and more.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="aiContext">Additional Context (optional)</Label>
              <Textarea
                id="aiContext"
                value={aiContext}
                onChange={(e) => setAiContext(e.target.value)}
                placeholder="Add any specific details for the AI to include: project requirements, client location, special terms, technology stack, timeline preferences, industry-specific clauses..."
                rows={4}
              />
              <p className="text-xs text-muted-foreground">
                The AI will use your client, title, description, and line items from previous steps along with any
                additional context you provide here.
              </p>
            </div>

            <div className="flex items-center gap-3">
              <Button
                type="button"
                onClick={generateProposal}
                disabled={isGenerating}
                className="bg-violet-600 hover:bg-violet-700"
              >
                {isGenerating ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Generating detailed proposal...
                  </>
                ) : (
                  <>
                    <Sparkles className="mr-2 h-4 w-4" />
                    {generatedContent ? "Regenerate Proposal" : "Generate Detailed Proposal"}
                  </>
                )}
              </Button>
              {generatedContent && (
                <span className="text-sm text-green-600 font-medium">Proposal generated successfully</span>
              )}
            </div>

            {generatedContent && (
              <div className="space-y-3 mt-4">
                <Separator />
                <div className="flex items-center justify-between">
                  <Label className="text-base font-semibold">Generated Proposal Preview</Label>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      navigator.clipboard.writeText(generatedContent);
                    }}
                  >
                    Copy to Clipboard
                  </Button>
                </div>
                <div className="rounded-lg border bg-muted/30 p-6 max-h-[600px] overflow-y-auto">
                  <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap">
                    {generatedContent}
                  </div>
                </div>
                <p className="text-xs text-muted-foreground">
                  This generated content will be saved with your proposal and can be used for the client-facing
                  document. You can edit it after the proposal is created.
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Step 4: Terms */}
      {currentStep === 4 && (
        <Card>
          <CardHeader>
            <CardTitle>Terms & Conditions</CardTitle>
            <CardDescription>Add any terms, conditions, or additional notes</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="terms">Terms & Conditions</Label>
              <Textarea id="terms" {...register("terms")} placeholder="Enter your terms and conditions..." rows={8} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="metadata.notes">Client Notes</Label>
              <Textarea
                id="metadata.notes"
                {...register("metadata.notes")}
                placeholder="Notes visible to the client..."
                rows={4}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="metadata.internalNotes">Internal Notes</Label>
              <Textarea
                id="metadata.internalNotes"
                {...register("metadata.internalNotes")}
                placeholder="Internal notes (not visible to client)..."
                rows={4}
              />
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 5: Review */}
      {currentStep === 5 && (
        <Card>
          <CardHeader>
            <CardTitle>Review Proposal</CardTitle>
            <CardDescription>Review your proposal before saving</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-2">
              <h3 className="font-semibold">Client</h3>
              <p className="text-sm text-muted-foreground">
                {clients.find((c) => c.id === watch("clientId"))?.company_name || "Not selected"}
              </p>
            </div>

            <div className="space-y-2">
              <h3 className="font-semibold">Title</h3>
              <p className="text-sm text-muted-foreground">{watch("title") || "No title"}</p>
            </div>

            {watch("description") && (
              <div className="space-y-2">
                <h3 className="font-semibold">Description</h3>
                <p className="text-sm text-muted-foreground">{watch("description")}</p>
              </div>
            )}

            <div className="space-y-2">
              <h3 className="font-semibold">Line Items</h3>
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Description</TableHead>
                      <TableHead>Quantity</TableHead>
                      <TableHead>Unit Price</TableHead>
                      <TableHead>Amount</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {lineItems.map((item, index) => (
                      <TableRow key={index}>
                        <TableCell>{item.description}</TableCell>
                        <TableCell>{item.quantity}</TableCell>
                        <TableCell>${Number(item.unitPrice).toFixed(2)}</TableCell>
                        <TableCell>${(Number(item.quantity) * Number(item.unitPrice)).toFixed(2)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
              <div className="flex justify-end mt-2">
                <div className="text-lg font-semibold">Total: ${totalAmount.toFixed(2)}</div>
              </div>
            </div>

            {generatedContent && (
              <div className="space-y-2">
                <h3 className="font-semibold flex items-center gap-2">
                  <Sparkles className="h-4 w-4 text-violet-500" />
                  AI-Generated Detailed Proposal
                </h3>
                <div className="rounded-lg border bg-muted/30 p-4 max-h-[400px] overflow-y-auto">
                  <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap text-sm">
                    {generatedContent.substring(0, 1000)}
                    {generatedContent.length > 1000 && (
                      <span className="text-muted-foreground">
                        ... ({Math.ceil(generatedContent.length / 1000)}k characters total)
                      </span>
                    )}
                  </div>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Navigation */}
      <div className="flex justify-between">
        <Button type="button" variant="outline" onClick={prevStep} disabled={currentStep === 1 || isSubmitting}>
          <ChevronLeft className="mr-2 h-4 w-4" />
          Previous
        </Button>

        <div className="flex gap-2">
          {currentStep < steps.length ? (
            <Button type="button" onClick={nextStep}>
              Next
              <ChevronRight className="ml-2 h-4 w-4" />
            </Button>
          ) : (
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Creating...
                </>
              ) : (
                <>
                  <FileText className="mr-2 h-4 w-4" />
                  Create Proposal
                </>
              )}
            </Button>
          )}
        </div>
      </div>
    </form>
  );
}
