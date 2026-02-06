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
import { MoreHorizontal, Eye, Edit, CheckCircle, XCircle } from "lucide-react";
import Link from "next/link";
import { formatDate } from "@/lib/utils";

type Referral = {
  id: string;
  partnerName: string;
  referredName: string;
  referredEmail: string;
  status: string;
  referredAt: string;
  convertedAt?: string;
  commissionAmount?: string;
};

const statusColors = {
  pending: "outline",
  contacted: "secondary",
  qualified: "default",
  converted: "default",
  rejected: "destructive",
  lost: "destructive",
} as const;

export function ReferralsTable() {
  const [referrals] = useState<Referral[]>([]);

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Partner</TableHead>
            <TableHead>Referred Contact</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Referred Date</TableHead>
            <TableHead>Converted Date</TableHead>
            <TableHead className="text-right">Commission</TableHead>
            <TableHead className="w-[50px]"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {referrals.length === 0 ? (
            <TableRow>
              <TableCell colSpan={7} className="text-center text-muted-foreground">
                No referrals found.
              </TableCell>
            </TableRow>
          ) : (
            referrals.map((referral) => (
              <TableRow key={referral.id}>
                <TableCell className="font-medium">{referral.partnerName}</TableCell>
                <TableCell>
                  <div>
                    <div>{referral.referredName}</div>
                    <div className="text-sm text-muted-foreground">{referral.referredEmail}</div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant={statusColors[referral.status as keyof typeof statusColors]}>{referral.status}</Badge>
                </TableCell>
                <TableCell>{formatDate(referral.referredAt)}</TableCell>
                <TableCell>{referral.convertedAt ? formatDate(referral.convertedAt) : "-"}</TableCell>
                <TableCell className="text-right">
                  {referral.commissionAmount ? `$${referral.commissionAmount}` : "-"}
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuLabel>Actions</DropdownMenuLabel>
                      <DropdownMenuItem asChild>
                        <Link href={`/referrals/${referral.id}`}>
                          <Eye className="mr-2 h-4 w-4" />
                          View Details
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={`/referrals/${referral.id}/edit`}>
                          <Edit className="mr-2 h-4 w-4" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem>
                        <CheckCircle className="mr-2 h-4 w-4" />
                        Mark as Converted
                      </DropdownMenuItem>
                      <DropdownMenuItem className="text-destructive">
                        <XCircle className="mr-2 h-4 w-4" />
                        Mark as Lost
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
