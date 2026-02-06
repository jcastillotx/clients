import { Suspense } from "react";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { projects, projectBudgets, projectCostEntries } from "@/lib/db/schema/projects";
import { eq, and, isNull, sql } from "drizzle-orm";
import { BudgetTracker } from "@/components/projects/budget-tracker";
import { Button } from "@/components/ui/button";
import { ArrowLeft } from "lucide-react";
import Link from "next/link";

async function getProjectBudget(id: string) {
  const [project] = await db
    .select()
    .from(projects)
    .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    return null;
  }

  // Fetch budgets with spent amounts
  const budgets = await db
    .select({
      budget: projectBudgets,
      totalSpent: sql<number>`COALESCE(SUM(${projectCostEntries.amount}), 0)`,
      entriesCount: sql<number>`COUNT(${projectCostEntries.id})`,
    })
    .from(projectBudgets)
    .leftJoin(projectCostEntries, eq(projectBudgets.id, projectCostEntries.budgetId))
    .where(eq(projectBudgets.projectId, id))
    .groupBy(projectBudgets.id);

  // Fetch all cost entries
  const costEntries = await db
    .select()
    .from(projectCostEntries)
    .where(eq(projectCostEntries.projectId, id))
    .orderBy(projectCostEntries.entryDate);

  return {
    project,
    budgets: budgets.map((row) => ({
      ...row.budget,
      totalSpent: Number(row.totalSpent),
      entriesCount: Number(row.entriesCount),
    })),
    costEntries,
  };
}

function BudgetLoading() {
  return (
    <div className="container mx-auto py-8 px-4">
      <div className="h-96 rounded-lg border bg-muted/50 animate-pulse" />
    </div>
  );
}

async function BudgetContent({ id }: { id: string }) {
  const data = await getProjectBudget(id);

  if (!data) {
    notFound();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" asChild>
          <Link href={`/projects/${id}`}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Project
          </Link>
        </Button>
      </div>

      <div>
        <h1 className="text-3xl font-bold">{data.project.name}</h1>
        <p className="text-muted-foreground mt-1">Budget Tracking</p>
      </div>

      <BudgetTracker budgets={data.budgets} costEntries={data.costEntries} currency={data.project.currency} />
    </div>
  );
}

export default function ProjectBudgetPage({ params }: { params: Promise<{ id: string }> }) {
  return (
    <div className="container mx-auto py-8 px-4">
      <Suspense fallback={<BudgetLoading />}>
        <AsyncBudgetContent params={params} />
      </Suspense>
    </div>
  );
}

async function AsyncBudgetContent({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <BudgetContent id={id} />;
}
