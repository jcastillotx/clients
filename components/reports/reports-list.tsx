import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { FileText, Download, Edit, Trash2 } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

// Mock data - replace with actual API call
const mockReports = [
  {
    id: "1",
    name: "Monthly Revenue Report",
    reportType: "financial",
    sectionsCount: 4,
    createdBy: "John Doe",
    createdAt: new Date("2024-01-15"),
    usageCount: 12,
  },
  {
    id: "2",
    name: "SEO Performance Dashboard",
    reportType: "seo",
    sectionsCount: 6,
    createdBy: "Jane Smith",
    createdAt: new Date("2024-01-20"),
    usageCount: 8,
  },
  {
    id: "3",
    name: "Project Status Summary",
    reportType: "project",
    sectionsCount: 3,
    createdBy: "Admin",
    createdAt: new Date("2024-02-01"),
    usageCount: 15,
  },
];

const reportTypeColors: Record<string, string> = {
  financial: "bg-green-500",
  seo: "bg-blue-500",
  project: "bg-purple-500",
  marketing: "bg-orange-500",
};

export async function ReportsList() {
  // const reports = await fetchReportTemplates();
  const reports = mockReports;

  return (
    <div className="space-y-4">
      {reports.length === 0 ? (
        <div className="text-center py-12">
          <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
          <p className="mt-4 text-muted-foreground">No report templates found.</p>
          <p className="text-sm text-muted-foreground">Create your first template to get started.</p>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Template Name</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Sections</TableHead>
              <TableHead>Created By</TableHead>
              <TableHead>Created</TableHead>
              <TableHead>Usage</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {reports.map((report) => (
              <TableRow key={report.id}>
                <TableCell className="font-medium">{report.name}</TableCell>
                <TableCell>
                  <Badge className={reportTypeColors[report.reportType]}>{report.reportType}</Badge>
                </TableCell>
                <TableCell>{report.sectionsCount}</TableCell>
                <TableCell>{report.createdBy}</TableCell>
                <TableCell>{new Date(report.createdAt).toLocaleDateString()}</TableCell>
                <TableCell>{report.usageCount} times</TableCell>
                <TableCell className="text-right">
                  <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="icon">
                      <Download className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon">
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon">
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
