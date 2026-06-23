"use client";

import { useEffect, useState } from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontal, Copy, BarChart, Trash2, Loader2 } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

type Survey = {
  id: string;
  title: string;
  description: string;
  clientName?: string;
  isActive: boolean;
  responseCount: number;
  createdAt: string;
};

export function SurveysTable() {
  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchSurveys = async () => {
      try {
        setLoading(true);
        setError(null);
        const rows = await fetchApi<
          Array<{
            id: string;
            title: string;
            description: string | null;
            is_active: boolean;
            response_count: number;
            created_at: string;
            client?: { company_name?: string } | Array<{ company_name?: string }> | null;
          }>
        >("/api/surveys", { method: "GET", cache: "no-store" }, {
          fallbackMessage: "Failed to fetch surveys",
        });

        const mapped = rows.map((row) => {
          const clientRelation = Array.isArray(row.client) ? row.client[0] : row.client;
          return {
            id: row.id,
            title: row.title,
            description: row.description || "",
            clientName: clientRelation?.company_name || "All Clients",
            isActive: row.is_active,
            responseCount: row.response_count || 0,
            createdAt: row.created_at,
          } satisfies Survey;
        });
        setSurveys(mapped);
      } catch (fetchError) {
        setError(fetchError instanceof Error ? fetchError.message : "Failed to fetch surveys");
      } finally {
        setLoading(false);
      }
    };

    void fetchSurveys();
  }, []);

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Title</TableHead>
            <TableHead>Client</TableHead>
            <TableHead>Status</TableHead>
            <TableHead className="text-right">Responses</TableHead>
            <TableHead>Created</TableHead>
            <TableHead className="w-[50px]"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {loading ? (
            <TableRow>
              <TableCell colSpan={6} className="text-center text-muted-foreground">
                <div className="flex items-center justify-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Loading surveys...
                </div>
              </TableCell>
            </TableRow>
          ) : error ? (
            <TableRow>
              <TableCell colSpan={6} className="text-center text-destructive">
                {error}
              </TableCell>
            </TableRow>
          ) : surveys.length === 0 ? (
            <TableRow>
              <TableCell colSpan={6} className="text-center text-muted-foreground">
                No surveys found. Create your first survey to collect feedback.
              </TableCell>
            </TableRow>
          ) : (
            surveys.map((survey) => (
              <TableRow key={survey.id}>
                <TableCell>
                  <div>
                    <div className="font-medium">{survey.title}</div>
                    {survey.description && (
                      <div className="text-sm text-muted-foreground line-clamp-1">{survey.description}</div>
                    )}
                  </div>
                </TableCell>
                <TableCell>{survey.clientName || "All Clients"}</TableCell>
                <TableCell>
                  <Badge variant={survey.isActive ? "default" : "secondary"}>
                    {survey.isActive ? "Active" : "Inactive"}
                  </Badge>
                </TableCell>
                <TableCell className="text-right">{survey.responseCount}</TableCell>
                <TableCell>{new Date(survey.createdAt).toLocaleDateString()}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuLabel>Actions</DropdownMenuLabel>
                      <DropdownMenuItem disabled>
                        <BarChart className="mr-2 h-4 w-4" />
                        Results (coming soon)
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem disabled>
                        <Copy className="mr-2 h-4 w-4" />
                        Duplicate (coming soon)
                      </DropdownMenuItem>
                      <DropdownMenuItem disabled className="text-destructive">
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete (coming soon)
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </div>
  );
}
