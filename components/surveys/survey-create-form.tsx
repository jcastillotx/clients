"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Plus, Trash2, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";

type QuestionType = "text" | "rating" | "nps" | "multiple_choice" | "checkbox";

interface ClientOption {
  id: string;
  company_name: string;
}

interface QuestionDraft {
  prompt: string;
  type: QuestionType;
  isRequired: boolean;
  optionsText: string;
}

interface SurveyCreateFormProps {
  clients: ClientOption[];
  canSelectClient: boolean;
  defaultClientId?: string;
}

const createDefaultQuestion = (): QuestionDraft => ({
  prompt: "",
  type: "text",
  isRequired: true,
  optionsText: "",
});

export function SurveyCreateForm({ clients, canSelectClient, defaultClientId }: SurveyCreateFormProps) {
  const router = useRouter();
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [clientId, setClientId] = useState(defaultClientId || "");
  const [isActive, setIsActive] = useState(true);
  const [anonymousAllowed, setAnonymousAllowed] = useState(true);
  const [questions, setQuestions] = useState<QuestionDraft[]>([createDefaultQuestion()]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const updateQuestion = (index: number, patch: Partial<QuestionDraft>) => {
    setQuestions((previous) => {
      const next = [...previous];
      next[index] = {
        ...next[index],
        ...patch,
      };
      return next;
    });
  };

  const removeQuestion = (index: number) => {
    setQuestions((previous) => previous.filter((_, currentIndex) => currentIndex !== index));
  };

  const addQuestion = () => {
    setQuestions((previous) => [...previous, createDefaultQuestion()]);
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const normalizedQuestions = questions.map((question) => {
        const usesOptions = question.type === "multiple_choice" || question.type === "checkbox";
        return {
          prompt: question.prompt.trim(),
          type: question.type,
          isRequired: question.isRequired,
          options: usesOptions
            ? question.optionsText
                .split("\n")
                .map((option) => option.trim())
                .filter(Boolean)
            : undefined,
        };
      });

      const response = await fetch("/api/surveys", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          title,
          description: description || null,
          clientId: canSelectClient ? (clientId && clientId !== "all_clients" ? clientId : null) : defaultClientId || null,
          isActive,
          anonymousAllowed,
          questions: normalizedQuestions,
        }),
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || "Failed to create survey");
      }

      router.push("/surveys");
      router.refresh();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Failed to create survey");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Survey Details</CardTitle>
          <CardDescription>Create a new client feedback survey.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {error ? <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div> : null}

          {canSelectClient ? (
            <div className="space-y-2">
              <Label htmlFor="client">Client (optional)</Label>
              <Select value={clientId} onValueChange={setClientId}>
                <SelectTrigger id="client">
                  <SelectValue placeholder="All clients" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all_clients">All clients</SelectItem>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}

          <div className="space-y-2">
            <Label htmlFor="title">Title</Label>
            <Input
              id="title"
              required
              value={title}
              onChange={(event) => setTitle(event.target.value)}
              placeholder="Quarterly satisfaction survey"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              rows={3}
              placeholder="Tell respondents what this survey is about."
            />
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="flex items-center justify-between rounded-md border px-3 py-2">
              <div>
                <p className="text-sm font-medium">Active</p>
                <p className="text-xs text-muted-foreground">Allow responses immediately</p>
              </div>
              <Switch checked={isActive} onCheckedChange={setIsActive} />
            </div>
            <div className="flex items-center justify-between rounded-md border px-3 py-2">
              <div>
                <p className="text-sm font-medium">Allow anonymous responses</p>
                <p className="text-xs text-muted-foreground">Users can submit without identity</p>
              </div>
              <Switch checked={anonymousAllowed} onCheckedChange={setAnonymousAllowed} />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Questions</CardTitle>
              <CardDescription>Add at least one question.</CardDescription>
            </div>
            <Button type="button" variant="outline" onClick={addQuestion}>
              <Plus className="mr-2 h-4 w-4" />
              Add Question
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {questions.map((question, index) => {
            const needsOptions = question.type === "multiple_choice" || question.type === "checkbox";
            return (
              <div key={index} className="space-y-3 rounded-md border p-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-medium">Question {index + 1}</p>
                  {questions.length > 1 ? (
                    <Button type="button" variant="ghost" size="icon" onClick={() => removeQuestion(index)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  ) : null}
                </div>

                <div className="space-y-2">
                  <Label>Prompt</Label>
                  <Input
                    required
                    value={question.prompt}
                    onChange={(event) => updateQuestion(index, { prompt: event.target.value })}
                    placeholder="How would you rate the service quality?"
                  />
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Question type</Label>
                    <Select value={question.type} onValueChange={(value: QuestionType) => updateQuestion(index, { type: value })}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="text">Text</SelectItem>
                        <SelectItem value="rating">Rating (1-5)</SelectItem>
                        <SelectItem value="nps">NPS (0-10)</SelectItem>
                        <SelectItem value="multiple_choice">Multiple choice</SelectItem>
                        <SelectItem value="checkbox">Checkbox (multiple answers)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="flex items-center justify-between rounded-md border px-3 py-2 mt-7">
                    <div>
                      <p className="text-sm font-medium">Required</p>
                    </div>
                    <Switch
                      checked={question.isRequired}
                      onCheckedChange={(value) => updateQuestion(index, { isRequired: value })}
                    />
                  </div>
                </div>

                {needsOptions ? (
                  <div className="space-y-2">
                    <Label>Options (one per line)</Label>
                    <Textarea
                      required
                      value={question.optionsText}
                      onChange={(event) => updateQuestion(index, { optionsText: event.target.value })}
                      rows={4}
                      placeholder={"Very satisfied\nSatisfied\nNeutral\nDissatisfied"}
                    />
                  </div>
                ) : null}
              </div>
            );
          })}
        </CardContent>
      </Card>

      <div className="flex justify-end gap-3">
        <Button type="button" variant="outline" onClick={() => router.push("/surveys")} disabled={isSubmitting}>
          Cancel
        </Button>
        <Button type="submit" disabled={isSubmitting || !title.trim()}>
          {isSubmitting ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Creating...
            </>
          ) : (
            "Create Survey"
          )}
        </Button>
      </div>
    </form>
  );
}
