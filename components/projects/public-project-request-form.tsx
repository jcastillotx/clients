"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ArrowLeft, CheckCircle2, Loader2 } from "lucide-react";
import {
  isTurnstileWidgetEnabled,
  TurnstileWidget,
} from "@/components/shared/turnstile-widget";
import { fetchApi } from "@/lib/api/client";

export function PublicProjectRequestForm() {
  const [companyName, setCompanyName] = useState("");
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [website, setWebsite] = useState("");
  const [industry, setIndustry] = useState("");
  const [address, setAddress] = useState("");
  const [city, setCity] = useState("");
  const [state, setState] = useState("");
  const [zipCode, setZipCode] = useState("");
  const [country, setCountry] = useState("United States");
  const [businessOverview, setBusinessOverview] = useState("");
  const [title, setTitle] = useState("");
  const [executiveSummary, setExecutiveSummary] = useState("");
  const [description, setDescription] = useState("");
  const [desiredOutcome, setDesiredOutcome] = useState("");
  const [priority, setPriority] = useState<"low" | "medium" | "high">("medium");
  const [requestedStartDate, setRequestedStartDate] = useState("");
  const [requestedLaunchDate, setRequestedLaunchDate] = useState("");
  const [budgetRange, setBudgetRange] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const [submittedReference, setSubmittedReference] = useState<string | null>(null);

  const turnstileRequired = isTurnstileWidgetEnabled();

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const data = await fetchApi<{ requestId?: string }>(
        "/api/public/project-requests",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            companyName,
            contactName,
            contactEmail,
            contactPhone: contactPhone || null,
            website: website || null,
            industry: industry || null,
            address: address || null,
            city: city || null,
            state: state || null,
            zipCode: zipCode || null,
            country: country || null,
            businessOverview: businessOverview || null,
            title,
            executiveSummary,
            description: description || null,
            desiredOutcome: desiredOutcome || null,
            priority,
            requestedStartDate: requestedStartDate || null,
            requestedLaunchDate: requestedLaunchDate || null,
            dueDate: requestedLaunchDate || null,
            budgetRange: budgetRange || null,
            turnstileToken: turnstileToken || null,
          }),
        },
        { fallbackMessage: "Failed to submit project request" },
      );

      setSubmittedReference(data?.requestId || "submitted");
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Failed to submit project request");
    } finally {
      setIsSubmitting(false);
    }
  }

  if (submittedReference) {
    return (
      <Card className="border-primary/20 shadow-sm">
        <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
          <CheckCircle2 className="h-12 w-12 text-primary" />
          <div className="space-y-2">
            <h2 className="text-2xl font-semibold">Request submitted</h2>
            <p className="max-w-2xl text-sm text-muted-foreground">
              Your organization and project details have been received. Our team will review the submission and follow up using the contact information you provided.
            </p>
            <p className="text-xs text-muted-foreground">Reference: {submittedReference}</p>
          </div>
          <Button asChild variant="outline">
            <Link href="/">Return to sign in</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Request a New Project</h1>
          <p className="mt-1 text-muted-foreground">
            Share your business details and project goals so our team can review and follow up with the right next steps.
          </p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back
          </Link>
        </Button>
      </div>

      {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

      <Card>
        <CardHeader>
          <CardTitle>Organization Information</CardTitle>
          <CardDescription>Tell us about your organization and primary point of contact.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="company-name">Company Name</Label>
              <Input id="company-name" required value={companyName} onChange={(event) => setCompanyName(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="industry">Industry</Label>
              <Input id="industry" value={industry} onChange={(event) => setIndustry(event.target.value)} />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="contact-name">Primary Contact</Label>
              <Input id="contact-name" required value={contactName} onChange={(event) => setContactName(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="contact-email">Contact Email</Label>
              <Input id="contact-email" type="email" required value={contactEmail} onChange={(event) => setContactEmail(event.target.value)} />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="contact-phone">Phone</Label>
              <Input id="contact-phone" value={contactPhone} onChange={(event) => setContactPhone(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="website">Website</Label>
              <Input id="website" value={website} onChange={(event) => setWebsite(event.target.value)} placeholder="https://example.com" />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="business-overview">Business Overview</Label>
            <Textarea
              id="business-overview"
              rows={4}
              value={businessOverview}
              onChange={(event) => setBusinessOverview(event.target.value)}
              placeholder="What your organization does, who you serve, and any context that will help us scope the engagement."
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="address">Street Address</Label>
            <Textarea id="address" rows={2} value={address} onChange={(event) => setAddress(event.target.value)} />
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div className="space-y-2">
              <Label htmlFor="city">City</Label>
              <Input id="city" value={city} onChange={(event) => setCity(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="state">State</Label>
              <Input id="state" value={state} onChange={(event) => setState(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="zip-code">ZIP / Postal Code</Label>
              <Input id="zip-code" value={zipCode} onChange={(event) => setZipCode(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="country">Country</Label>
              <Input id="country" value={country} onChange={(event) => setCountry(event.target.value)} />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Project Intake</CardTitle>
          <CardDescription>Capture the business need, goals, and timing for the new engagement.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">Project Title</Label>
            <Input id="title" required value={title} onChange={(event) => setTitle(event.target.value)} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="executive-summary">Executive Summary</Label>
            <Textarea
              id="executive-summary"
              required
              rows={5}
              value={executiveSummary}
              onChange={(event) => setExecutiveSummary(event.target.value)}
              placeholder="Describe the project, why it matters, and what a successful outcome looks like."
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Detailed Scope</Label>
            <Textarea id="description" rows={6} value={description} onChange={(event) => setDescription(event.target.value)} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="desired-outcome">Desired Outcome / KPIs</Label>
            <Textarea id="desired-outcome" rows={4} value={desiredOutcome} onChange={(event) => setDesiredOutcome(event.target.value)} />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="priority">Priority</Label>
              <Select value={priority} onValueChange={(value: "low" | "medium" | "high") => setPriority(value)}>
                <SelectTrigger id="priority">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="budget-range">Budget Range</Label>
              <Input id="budget-range" value={budgetRange} onChange={(event) => setBudgetRange(event.target.value)} placeholder="Example: $15,000 - $25,000" />
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="requested-start">Requested Start Date</Label>
              <Input id="requested-start" type="date" value={requestedStartDate} onChange={(event) => setRequestedStartDate(event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="requested-launch">Requested Due / Launch Date</Label>
              <Input id="requested-launch" type="date" value={requestedLaunchDate} onChange={(event) => setRequestedLaunchDate(event.target.value)} />
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="flex flex-col items-end gap-3">
        <TurnstileWidget
          onVerify={setTurnstileToken}
          onExpire={() => setTurnstileToken(null)}
          onError={() => setTurnstileToken(null)}
        />
        <Button
          type="submit"
          disabled={
            isSubmitting ||
            !companyName ||
            !contactName ||
            !contactEmail ||
            !title ||
            !executiveSummary ||
            (turnstileRequired && !turnstileToken)
          }
        >
          {isSubmitting ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Submitting...
            </>
          ) : (
            "Submit Project Request"
          )}
        </Button>
      </div>
    </form>
  );
}
