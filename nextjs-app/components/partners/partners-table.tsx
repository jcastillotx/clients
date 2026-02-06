"use client";

import { useState } from "react";
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
import { MoreHorizontal, Eye, Edit, Trash2, Ban, CheckCircle } from "lucide-react";
import Link from "next/link";
import { formatCurrency } from "@/lib/utils";

type Partner = {
  id: string;
  companyName: string;
  contactName: string;
  email: string;
  partnerType: string;
  status: string;
  commissionRate: string;
  totalReferrals: number;
  totalRevenue: string;
  code: string;
  createdAt: string;
};

// Mock data - replace with actual API call
const mockPartners: Partner[] = [
  {
    id: "1",
    companyName: "Digital Marketing Agency",
    contactName: "John Smith",
    email: "john@digitalagency.com",
    partnerType: "agency",
    status: "active",
    commissionRate: "15.00",
    totalReferrals: 12,
    totalRevenue: "24500.00",
    code: "DMA2024",
    createdAt: "2024-01-15",
  },
];

const statusColors = {
  active: "default",
  inactive: "secondary",
  pending: "outline",
  suspended: "destructive",
} as const;

const partnerTypeLabels = {
  agency: "Agency",
  affiliate: "Affiliate",
  reseller: "Reseller",
  strategic: "Strategic",
};

export function PartnersTable() {
  const [partners] = useState<Partner[]>(mockPartners);

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Company</TableHead>
            <TableHead>Contact</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Code</TableHead>
            <TableHead>Commission</TableHead>
            <TableHead className="text-right">Referrals</TableHead>
            <TableHead className="text-right">Revenue</TableHead>
            <TableHead className="w-[50px]"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {partners.length === 0 ? (
            <TableRow>
              <TableCell colSpan={9} className="text-center text-muted-foreground">
                No partners found. Create your first partner to get started.
              </TableCell>
            </TableRow>
          ) : (
            partners.map((partner) => (
              <TableRow key={partner.id}>
                <TableCell>
                  <div>
                    <div className="font-medium">{partner.companyName}</div>
                    <div className="text-sm text-muted-foreground">{partner.email}</div>
                  </div>
                </TableCell>
                <TableCell>{partner.contactName}</TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {partnerTypeLabels[partner.partnerType as keyof typeof partnerTypeLabels]}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge variant={statusColors[partner.status as keyof typeof statusColors]}>{partner.status}</Badge>
                </TableCell>
                <TableCell>
                  <code className="text-xs bg-muted px-2 py-1 rounded">{partner.code}</code>
                </TableCell>
                <TableCell>{partner.commissionRate}%</TableCell>
                <TableCell className="text-right">{partner.totalReferrals}</TableCell>
                <TableCell className="text-right">{formatCurrency(parseFloat(partner.totalRevenue))}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <MoreHorizontal className="h-4 w-4" />
                        <span className="sr-only">Open menu</span>
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuLabel>Actions</DropdownMenuLabel>
                      <DropdownMenuItem asChild>
                        <Link href={`/partners/${partner.id}`}>
                          <Eye className="mr-2 h-4 w-4" />
                          View Details
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={`/partners/${partner.id}/edit`}>
                          <Edit className="mr-2 h-4 w-4" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      {partner.status === "active" ? (
                        <DropdownMenuItem>
                          <Ban className="mr-2 h-4 w-4" />
                          Suspend
                        </DropdownMenuItem>
                      ) : (
                        <DropdownMenuItem>
                          <CheckCircle className="mr-2 h-4 w-4" />
                          Activate
                        </DropdownMenuItem>
                      )}
                      <DropdownMenuItem className="text-destructive">
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
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
