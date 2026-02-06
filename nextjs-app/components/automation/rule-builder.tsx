"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Plus, Trash2, Zap, Filter, PlayCircle } from "lucide-react";
import { Separator } from "@/components/ui/separator";

interface RuleBuilderProps {
  rule: any;
  onChange: (rule: any) => void;
}

const TRIGGERS = [
  { value: "request.created", label: "Request Created" },
  { value: "request.updated", label: "Request Updated" },
  { value: "request.status_changed", label: "Request Status Changed" },
  { value: "invoice.created", label: "Invoice Created" },
  { value: "invoice.paid", label: "Invoice Paid" },
  { value: "invoice.overdue", label: "Invoice Overdue" },
  { value: "project.created", label: "Project Created" },
  { value: "project.status_changed", label: "Project Status Changed" },
  { value: "meeting.scheduled", label: "Meeting Scheduled" },
  { value: "ticket.created", label: "Ticket Created" },
  { value: "schedule.daily", label: "Daily Schedule" },
  { value: "schedule.weekly", label: "Weekly Schedule" },
  { value: "schedule.monthly", label: "Monthly Schedule" },
];

const ACTION_TYPES = [
  { value: "send_email", label: "Send Email" },
  { value: "create_task", label: "Create Task" },
  { value: "update_status", label: "Update Status" },
  { value: "send_notification", label: "Send Notification" },
  { value: "webhook", label: "Webhook" },
];

const OPERATORS = [
  { value: "equals", label: "Equals" },
  { value: "not_equals", label: "Not Equals" },
  { value: "contains", label: "Contains" },
  { value: "greater_than", label: "Greater Than" },
  { value: "less_than", label: "Less Than" },
];

export function RuleBuilder({ rule, onChange }: RuleBuilderProps) {
  const addCondition = () => {
    onChange({
      ...rule,
      conditions: {
        ...rule.conditions,
        rules: [...(rule.conditions.rules || []), { field: "", operator: "equals", value: "" }],
      },
    });
  };

  const removeCondition = (index: number) => {
    const newRules = [...(rule.conditions.rules || [])];
    newRules.splice(index, 1);
    onChange({
      ...rule,
      conditions: { ...rule.conditions, rules: newRules },
    });
  };

  const updateCondition = (index: number, field: string, value: any) => {
    const newRules = [...(rule.conditions.rules || [])];
    newRules[index] = { ...newRules[index], [field]: value };
    onChange({
      ...rule,
      conditions: { ...rule.conditions, rules: newRules },
    });
  };

  const addAction = () => {
    onChange({
      ...rule,
      actions: [...rule.actions, { type: "send_email", config: {} }],
    });
  };

  const removeAction = (index: number) => {
    const newActions = [...rule.actions];
    newActions.splice(index, 1);
    onChange({ ...rule, actions: newActions });
  };

  const updateAction = (index: number, field: string, value: any) => {
    const newActions = [...rule.actions];
    if (field === "type") {
      newActions[index] = { type: value, config: {} };
    } else {
      newActions[index] = {
        ...newActions[index],
        config: { ...newActions[index].config, [field]: value },
      };
    }
    onChange({ ...rule, actions: newActions });
  };

  return (
    <div className="space-y-6">
      {/* Trigger Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Zap className="h-5 w-5" />
            Trigger
          </CardTitle>
          <CardDescription>When should this automation run?</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <Label htmlFor="trigger">Event Trigger *</Label>
            <Select value={rule.trigger} onValueChange={(value) => onChange({ ...rule, trigger: value })}>
              <SelectTrigger id="trigger">
                <SelectValue placeholder="Select a trigger event" />
              </SelectTrigger>
              <SelectContent>
                {TRIGGERS.map((trigger) => (
                  <SelectItem key={trigger.value} value={trigger.value}>
                    {trigger.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Conditions Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="h-5 w-5" />
            Conditions
          </CardTitle>
          <CardDescription>Optional: Define when this rule should apply</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-2">
            <Label>Match</Label>
            <Select
              value={rule.conditions?.operator || "and"}
              onValueChange={(value) =>
                onChange({
                  ...rule,
                  conditions: { ...rule.conditions, operator: value },
                })
              }
            >
              <SelectTrigger className="w-32">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="and">All</SelectItem>
                <SelectItem value="or">Any</SelectItem>
              </SelectContent>
            </Select>
            <span className="text-sm text-muted-foreground">of the following conditions</span>
          </div>

          <Separator />

          <div className="space-y-3">
            {(rule.conditions?.rules || []).map((condition: any, index: number) => (
              <div key={index} className="flex items-center gap-2">
                <Input
                  placeholder="Field name"
                  value={condition.field}
                  onChange={(e) => updateCondition(index, "field", e.target.value)}
                  className="flex-1"
                />
                <Select value={condition.operator} onValueChange={(value) => updateCondition(index, "operator", value)}>
                  <SelectTrigger className="w-40">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {OPERATORS.map((op) => (
                      <SelectItem key={op.value} value={op.value}>
                        {op.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Input
                  placeholder="Value"
                  value={condition.value}
                  onChange={(e) => updateCondition(index, "value", e.target.value)}
                  className="flex-1"
                />
                <Button variant="ghost" size="icon" onClick={() => removeCondition(index)}>
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>

          <Button variant="outline" onClick={addCondition} className="w-full">
            <Plus className="mr-2 h-4 w-4" />
            Add Condition
          </Button>
        </CardContent>
      </Card>

      {/* Actions Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <PlayCircle className="h-5 w-5" />
            Actions
          </CardTitle>
          <CardDescription>What should happen when the rule triggers? *</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-4">
            {rule.actions.map((action: any, index: number) => (
              <Card key={index}>
                <CardContent className="pt-6 space-y-4">
                  <div className="flex items-center justify-between">
                    <Badge>{index + 1}</Badge>
                    <Button variant="ghost" size="icon" onClick={() => removeAction(index)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>

                  <div className="space-y-2">
                    <Label>Action Type</Label>
                    <Select value={action.type} onValueChange={(value) => updateAction(index, "type", value)}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {ACTION_TYPES.map((type) => (
                          <SelectItem key={type.value} value={type.value}>
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {action.type === "send_email" && (
                    <>
                      <div className="space-y-2">
                        <Label>Recipients (comma-separated)</Label>
                        <Input
                          placeholder="email@example.com, another@example.com"
                          value={action.config.recipients?.join(", ") || ""}
                          onChange={(e) =>
                            updateAction(
                              index,
                              "recipients",
                              e.target.value.split(",").map((s) => s.trim()),
                            )
                          }
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Email Template</Label>
                        <Input
                          placeholder="Template name or ID"
                          value={action.config.template || ""}
                          onChange={(e) => updateAction(index, "template", e.target.value)}
                        />
                      </div>
                    </>
                  )}

                  {action.type === "create_task" && (
                    <>
                      <div className="space-y-2">
                        <Label>Task Title</Label>
                        <Input
                          placeholder="Task title"
                          value={action.config.title || ""}
                          onChange={(e) => updateAction(index, "title", e.target.value)}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Assignee Email</Label>
                        <Input
                          placeholder="assignee@example.com"
                          value={action.config.assignee || ""}
                          onChange={(e) => updateAction(index, "assignee", e.target.value)}
                        />
                      </div>
                    </>
                  )}

                  {action.type === "update_status" && (
                    <div className="space-y-2">
                      <Label>New Status</Label>
                      <Input
                        placeholder="e.g., in_progress, completed"
                        value={action.config.status || ""}
                        onChange={(e) => updateAction(index, "status", e.target.value)}
                      />
                    </div>
                  )}

                  {action.type === "send_notification" && (
                    <div className="space-y-2">
                      <Label>Message</Label>
                      <Input
                        placeholder="Notification message"
                        value={action.config.message || ""}
                        onChange={(e) => updateAction(index, "message", e.target.value)}
                      />
                    </div>
                  )}

                  {action.type === "webhook" && (
                    <div className="space-y-2">
                      <Label>Webhook URL</Label>
                      <Input
                        placeholder="https://api.example.com/webhook"
                        value={action.config.url || ""}
                        onChange={(e) => updateAction(index, "url", e.target.value)}
                      />
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>

          <Button variant="outline" onClick={addAction} className="w-full">
            <Plus className="mr-2 h-4 w-4" />
            Add Action
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
