import { Suspense } from "react";
import Link from "next/link";
import { desc, eq, isNull, sql } from "drizzle-orm";
import { DollarSign, Plus } from "lucide-react";
import { db } from "@/lib/db";
import { projects, projectBudgets } from "@/lib/db/schema/projects";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

async function getProjectBudgets() {
  const rows = await db
    .select({
      project: projects,
      allocatedTotal: sql<number>`COALESCE(SUM(${projectBudgets.allocatedAmount}), 0)`,
      spentTotal: sql<number>`COALESCE(SUM(${projectBudgets.spentAmount}), 0)`,
    })
    .from(projects)
    .leftJoin(projectBudgets, eq(projects.id, projectBudgets.projectId))
    .where(isNull(projects.deletedAt))
    .groupBy(projects.id)
    .orderBy(desc(projects.createdAt));

  return rows.map((row) => ({
    ...row.project,
    allocatedTotal: Number(row.allocatedTotal),
    spentTotal: Number(row.spentTotal),
  }));
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(amount);
}

async function BudgetsTable() {
  const items = await getProjectBudgets();

  if (items.length === 0) {
    return (
      <Card>
        <CardContent className="py-12 text-center">
          <p className="text-muted-foreground mb-4">No projects yet. Create a project to track budgets.</p>
          <Button asChild>
            <Link href="/projects/new">
              <Plus className="mr-2 h-4 w-4" />
              New Project
            </Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Project Budget Overview</CardTitle>
        <CardDescription>Allocated and spent amounts across active projects</CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Project</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Allocated</TableHead>
              <TableHead className="text-right">Spent</TableHead>
              <TableHead className="text-right">Remaining</TableHead>
              <TableHead />
            </TableRow>
          </TableHeader>
          <TableBody>
            {items.map((project) => {
              const remaining = project.allocatedTotal - project.spentTotal;
              return (
                <TableRow key={project.id}>
                  <TableCell className="font-medium">{project.name}</TableCell>
                  <TableCell className="capitalize">{project.status.replace(/_/g, " ")}</TableCell>
                  <TableCell className="text-right">{formatCurrency(project.allocatedTotal)}</TableCell>
                  <TableCell className="text-right">{formatCurrency(project.spentTotal)}</TableCell>
                  <TableCell className="text-right">{formatCurrency(remaining)}</TableCell>
                  <TableCell className="text-right">
                    <Button variant="outline" size="sm" asChild>
                      <Link href={`/projects/${project.id}/budget`}>Manage</Link>
                    </Button>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}

export default function ProjectBudgetsPage() {
  return (
    <div className="container mx-auto py-8 px-4">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <DollarSign className="h-8 w-8" />
            Project Budgets
          </h1>
          <p className="text-muted-foreground mt-1">Track allocated and spent budget across all projects</p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/projects">All Projects</Link>
        </Button>
      </div>

      <Suspense
        fallback={
          <Card>
            <CardContent className="py-12 text-center text-muted-foreground">Loading budgets...</CardContent>
          </Card>
        }
      >
        <BudgetsTable />
      </Suspense>
    </div>
  );
}
