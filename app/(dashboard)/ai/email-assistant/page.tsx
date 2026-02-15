"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Mail, Sparkles, Copy, Send, RefreshCw, Loader2 } from "lucide-react";
import { toast } from "sonner";

type EmailTone = "professional" | "friendly" | "formal" | "casual";
type EmailPurpose = "introduction" | "follow-up" | "update" | "proposal" | "support" | "thank-you" | "custom";

export default function EmailAssistantPage() {
  const [purpose, setPurpose] = useState<EmailPurpose>("custom");
  const [tone, setTone] = useState<EmailTone>("professional");
  const [recipient, setRecipient] = useState("");
  const [subject, setSubject] = useState("");
  const [keyPoints, setKeyPoints] = useState("");
  const [customInstructions, setCustomInstructions] = useState("");
  const [generatedEmail, setGeneratedEmail] = useState("");
  const [isGenerating, setIsGenerating] = useState(false);

  async function generateEmail() {
    if (!keyPoints.trim() && !customInstructions.trim()) {
      toast.error("Please provide key points or custom instructions");
      return;
    }

    setIsGenerating(true);

    try {
      const response = await fetch("/api/ai/generate-email", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          purpose,
          tone,
          recipient,
          subject,
          keyPoints,
          customInstructions,
        }),
      });

      if (!response.ok) {
        throw new Error("Failed to generate email");
      }

      const data = await response.json();
      setGeneratedEmail(data.email);
      toast.success("Email generated successfully!");
    } catch (error) {
      console.error("Error generating email:", error);
      toast.error("Failed to generate email. Please try again.");
    } finally {
      setIsGenerating(false);
    }
  }

  function copyToClipboard() {
    navigator.clipboard.writeText(generatedEmail);
    toast.success("Email copied to clipboard!");
  }

  function clearForm() {
    setRecipient("");
    setSubject("");
    setKeyPoints("");
    setCustomInstructions("");
    setGeneratedEmail("");
  }

  const emailTemplates = {
    introduction: {
      label: "Introduction Email",
      description: "Introduce your company or services to a new client",
      placeholder: "- Company name\n- Services offered\n- Why they should work with you",
    },
    "follow-up": {
      label: "Follow-up Email",
      description: "Follow up on a previous conversation or meeting",
      placeholder: "- What you discussed\n- Action items\n- Next steps",
    },
    update: {
      label: "Project Update",
      description: "Send updates on project progress",
      placeholder: "- Project status\n- Completed tasks\n- Upcoming milestones",
    },
    proposal: {
      label: "Proposal Email",
      description: "Send a business proposal or quote",
      placeholder: "- Services/products\n- Pricing\n- Timeline\n- Benefits",
    },
    support: {
      label: "Support Response",
      description: "Respond to a support request",
      placeholder: "- Issue description\n- Solution provided\n- Next steps",
    },
    "thank-you": {
      label: "Thank You Email",
      description: "Thank a client for their business",
      placeholder: "- What you're thankful for\n- Future plans\n- Personal touch",
    },
    custom: {
      label: "Custom Email",
      description: "Create any type of email",
      placeholder: "- Main message points\n- Specific details\n- Call to action",
    },
  };

  const currentTemplate = emailTemplates[purpose];

  return (
    <div className="container mx-auto p-6 max-w-7xl">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
          <Mail className="h-8 w-8" />
          AI Email Assistant
        </h1>
        <p className="text-muted-foreground mt-2">
          Generate professional emails using AI - perfect for client communication, proposals, and updates
        </p>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Input Form */}
        <Card>
          <CardHeader>
            <CardTitle>Email Details</CardTitle>
            <CardDescription>Provide information about the email you want to create</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="purpose">Email Purpose</Label>
              <Select value={purpose} onValueChange={(val) => setPurpose(val as EmailPurpose)}>
                <SelectTrigger id="purpose" className="mt-2">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(emailTemplates).map(([key, template]) => (
                    <SelectItem key={key} value={key}>
                      {template.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground mt-1">{currentTemplate.description}</p>
            </div>

            <div>
              <Label htmlFor="tone">Tone</Label>
              <Select value={tone} onValueChange={(val) => setTone(val as EmailTone)}>
                <SelectTrigger id="tone" className="mt-2">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="professional">Professional</SelectItem>
                  <SelectItem value="friendly">Friendly</SelectItem>
                  <SelectItem value="formal">Formal</SelectItem>
                  <SelectItem value="casual">Casual</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="recipient">Recipient Name (Optional)</Label>
              <Input
                id="recipient"
                placeholder="e.g., John Smith"
                value={recipient}
                onChange={(e) => setRecipient(e.target.value)}
                className="mt-2"
              />
            </div>

            <div>
              <Label htmlFor="subject">Email Subject (Optional)</Label>
              <Input
                id="subject"
                placeholder="AI will suggest one if left blank"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                className="mt-2"
              />
            </div>

            <div>
              <Label htmlFor="keyPoints">Key Points</Label>
              <Textarea
                id="keyPoints"
                placeholder={currentTemplate.placeholder}
                value={keyPoints}
                onChange={(e) => setKeyPoints(e.target.value)}
                className="mt-2 min-h-[120px]"
              />
            </div>

            <div>
              <Label htmlFor="customInstructions">Additional Instructions (Optional)</Label>
              <Textarea
                id="customInstructions"
                placeholder="e.g., Keep it brief, mention our upcoming meeting, include pricing..."
                value={customInstructions}
                onChange={(e) => setCustomInstructions(e.target.value)}
                className="mt-2 min-h-[80px]"
              />
            </div>

            <div className="flex gap-2 pt-4">
              <Button onClick={generateEmail} disabled={isGenerating} className="flex-1 gap-2">
                {isGenerating ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Generating...
                  </>
                ) : (
                  <>
                    <Sparkles className="h-4 w-4" />
                    Generate Email
                  </>
                )}
              </Button>
              <Button variant="outline" onClick={clearForm} disabled={isGenerating}>
                Clear
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Generated Email */}
        <Card>
          <CardHeader>
            <CardTitle>Generated Email</CardTitle>
            <CardDescription>AI-generated email based on your inputs</CardDescription>
          </CardHeader>
          <CardContent>
            {generatedEmail ? (
              <div className="space-y-4">
                <Tabs defaultValue="preview">
                  <TabsList className="grid w-full grid-cols-2">
                    <TabsTrigger value="preview">Preview</TabsTrigger>
                    <TabsTrigger value="raw">Plain Text</TabsTrigger>
                  </TabsList>
                  <TabsContent value="preview" className="mt-4">
                    <div className="rounded-lg border bg-background p-6 space-y-4">
                      {subject && (
                        <div>
                          <Label className="text-xs text-muted-foreground">Subject</Label>
                          <p className="font-semibold mt-1">{subject}</p>
                        </div>
                      )}
                      <div className="whitespace-pre-wrap text-sm leading-relaxed">{generatedEmail}</div>
                    </div>
                  </TabsContent>
                  <TabsContent value="raw" className="mt-4">
                    <Textarea value={generatedEmail} readOnly className="min-h-[400px] font-mono text-sm" />
                  </TabsContent>
                </Tabs>

                <div className="flex gap-2">
                  <Button onClick={copyToClipboard} variant="outline" className="flex-1 gap-2">
                    <Copy className="h-4 w-4" />
                    Copy to Clipboard
                  </Button>
                  <Button onClick={generateEmail} variant="outline" className="gap-2">
                    <RefreshCw className="h-4 w-4" />
                    Regenerate
                  </Button>
                </div>

                <div className="flex gap-2">
                  <Button className="flex-1 gap-2">
                    <Send className="h-4 w-4" />
                    Send via Email Client
                  </Button>
                </div>
              </div>
            ) : (
              <div className="flex flex-col items-center justify-center text-center p-12 border-2 border-dashed rounded-lg min-h-[400px]">
                <Mail className="h-16 w-16 text-muted-foreground mb-4" />
                <h3 className="text-lg font-semibold mb-2">No Email Generated Yet</h3>
                <p className="text-sm text-muted-foreground max-w-md">
                  Fill in the details on the left and click "Generate Email" to create an AI-powered email draft
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Quick Templates */}
      <div className="mt-6">
        <h2 className="text-lg font-semibold mb-4">Quick Templates</h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {Object.entries(emailTemplates)
            .filter(([key]) => key !== "custom")
            .map(([key, template]) => (
              <Card key={key} className="cursor-pointer hover:border-primary transition-colors" onClick={() => setPurpose(key as EmailPurpose)}>
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm flex items-center gap-2">
                    <Mail className="h-4 w-4" />
                    {template.label}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-xs text-muted-foreground">{template.description}</p>
                </CardContent>
              </Card>
            ))}
        </div>
      </div>
    </div>
  );
}
