"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";
import Link from "next/link";
import { fetchApi } from "@/lib/api/client";

export default function NewProjectPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    clientId: "",
    status: "planning",
    startDate: "",
    endDate: "",
    estimatedHours: "",
    budgetAmount: "",
    currency: "USD",
    projectManagerId: "",
  });

  const [budgets, setBudgets] = useState<Array<{ category: string; allocatedAmount: string; notes: string }>>([]);

  const [milestones, setMilestones] = useState<Array<{ title: string; description: string; dueDate: string }>>([]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const project = await fetchApi<{ id: string }>(
        "/api/projects",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            ...formData,
            estimatedHours: formData.estimatedHours ? parseFloat(formData.estimatedHours) : null,
            budgetAmount: formData.budgetAmount ? parseFloat(formData.budgetAmount) : null,
            budgets: budgets.filter((b) => b.category && b.allocatedAmount),
            milestones: milestones.filter((m) => m.title),
          }),
        },
        { fallbackMessage: "Failed to create project" },
      );

      router.push(`/projects/${project.id}`);
    } catch (error) {
      console.error("Error creating project:", error);
      alert(error instanceof Error ? error.message : "Failed to create project");
    } finally {
      setLoading(false);
    }
  };

  const addBudget = () => {
    setBudgets([...budgets, { category: "development", allocatedAmount: "", notes: "" }]);
  };

  const removeBudget = (index: number) => {
    setBudgets(budgets.filter((_, i) => i !== index));
  };

  const updateBudget = (index: number, field: string, value: string) => {
    const updated = [...budgets];
    updated[index] = { ...updated[index], [field]: value };
    setBudgets(updated);
  };

  const addMilestone = () => {
    setMilestones([...milestones, { title: "", description: "", dueDate: "" }]);
  };

  const removeMilestone = (index: number) => {
    setMilestones(milestones.filter((_, i) => i !== index));
  };

  const updateMilestone = (index: number, field: string, value: string) => {
    const updated = [...milestones];
    updated[index] = { ...updated[index], [field]: value };
    setMilestones(updated);
  };

  return (
    <div className="container mx-auto py-8 px-4 max-w-4xl">
      <div className="flex items-center gap-4 mb-8">
        <Button variant="ghost" size="sm" asChild>
          <Link href="/projects">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Projects
          </Link>
        </Button>
      </div>

      <div className="mb-8">
        <h1 className="text-3xl font-bold">Create New Project</h1>
        <p className="text-muted-foreground mt-1">Set up a new project with milestones and budget tracking</p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Information */}
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
            <CardDescription>Core project details</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Project Name *</Label>
              <Input
                id="name"
                required
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Enter project name"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter project description"
                rows={4}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="clientId">Client ID *</Label>
                <Input
                  id="clientId"
                  required
                  value={formData.clientId}
                  onChange={(e) => setFormData({ ...formData, clientId: e.target.value })}
                  placeholder="Enter client ID"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <Select value={formData.status} onValueChange={(value) => setFormData({ ...formData, status: value })}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="planning">Planning</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="on_hold">On Hold</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="startDate">Start Date</Label>
                <Input
                  id="startDate"
                  type="date"
                  value={formData.startDate}
                  onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="endDate">End Date</Label>
                <Input
                  id="endDate"
                  type="date"
                  value={formData.endDate}
                  onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Budget Information */}
        <Card>
          <CardHeader>
            <CardTitle>Budget Information</CardTitle>
            <CardDescription>Set project budget and allocations</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="budgetAmount">Total Budget</Label>
                <Input
                  id="budgetAmount"
                  type="number"
                  step="0.01"
                  value={formData.budgetAmount}
                  onChange={(e) => setFormData({ ...formData, budgetAmount: e.target.value })}
                  placeholder="0.00"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="currency">Currency</Label>
                <Select
                  value={formData.currency}
                  onValueChange={(value) => setFormData({ ...formData, currency: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="USD">USD</SelectItem>
                    <SelectItem value="EUR">EUR</SelectItem>
                    <SelectItem value="GBP">GBP</SelectItem>
                    <SelectItem value="CAD">CAD</SelectItem>
                    <SelectItem value="AUD">AUD</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="estimatedHours">Estimated Hours</Label>
              <Input
                id="estimatedHours"
                type="number"
                step="0.5"
                value={formData.estimatedHours}
                onChange={(e) => setFormData({ ...formData, estimatedHours: e.target.value })}
                placeholder="0"
              />
            </div>

            {/* Budget Categories */}
            <div className="pt-4 border-t">
              <div className="flex justify-between items-center mb-4">
                <Label>Budget Categories</Label>
                <Button type="button" variant="outline" size="sm" onClick={addBudget}>
                  <Plus className="h-4 w-4 mr-2" />
                  Add Category
                </Button>
              </div>

              <div className="space-y-3">
                {budgets.map((budget, index) => (
                  <div key={index} className="flex gap-2 items-start">
                    <Select value={budget.category} onValueChange={(value) => updateBudget(index, "category", value)}>
                      <SelectTrigger className="w-[200px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="development">Development</SelectItem>
                        <SelectItem value="design">Design</SelectItem>
                        <SelectItem value="marketing">Marketing</SelectItem>
                        <SelectItem value="infrastructure">Infrastructure</SelectItem>
                        <SelectItem value="other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                    <Input
                      type="number"
                      step="0.01"
                      placeholder="Amount"
                      value={budget.allocatedAmount}
                      onChange={(e) => updateBudget(index, "allocatedAmount", e.target.value)}
                      className="flex-1"
                    />
                    <Button type="button" variant="ghost" size="sm" onClick={() => removeBudget(index)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Milestones */}
        <Card>
          <CardHeader>
            <CardTitle>Milestones</CardTitle>
            <CardDescription>Define project milestones</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex justify-between items-center">
              <Label>Project Milestones</Label>
              <Button type="button" variant="outline" size="sm" onClick={addMilestone}>
                <Plus className="h-4 w-4 mr-2" />
                Add Milestone
              </Button>
            </div>

            <div className="space-y-4">
              {milestones.map((milestone, index) => (
                <div key={index} className="p-4 border rounded-lg space-y-3">
                  <div className="flex justify-between items-start">
                    <Label>Milestone {index + 1}</Label>
                    <Button type="button" variant="ghost" size="sm" onClick={() => removeMilestone(index)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                  <Input
                    placeholder="Milestone title"
                    value={milestone.title}
                    onChange={(e) => updateMilestone(index, "title", e.target.value)}
                  />
                  <Textarea
                    placeholder="Description"
                    value={milestone.description}
                    onChange={(e) => updateMilestone(index, "description", e.target.value)}
                    rows={2}
                  />
                  <Input
                    type="date"
                    value={milestone.dueDate}
                    onChange={(e) => updateMilestone(index, "dueDate", e.target.value)}
                  />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Submit */}
        <div className="flex justify-end gap-4">
          <Button type="button" variant="outline" asChild>
            <Link href="/projects">Cancel</Link>
          </Button>
          <Button type="submit" disabled={loading}>
            {loading ? "Creating..." : "Create Project"}
          </Button>
        </div>
      </form>
    </div>
  );
}
