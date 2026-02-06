import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { timeEntries } from "@/lib/db/schema/time-tracking";
import { eq, and, gte, lte, sql } from "drizzle-orm";
import { auth } from "@clerk/nextjs/server";

/**
 * GET /api/time-tracking/reports
 * Generate time tracking reports
 */
export async function GET(request: NextRequest) {
  try {
    const { userId } = await auth();
    if (!userId) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const searchParams = request.nextUrl.searchParams;
    const startDate = searchParams.get("startDate");
    const endDate = searchParams.get("endDate");
    const groupBy = searchParams.get("groupBy") || "day"; // day, week, month, client, request

    if (!startDate || !endDate) {
      return NextResponse.json({ error: "Start date and end date are required" }, { status: 400 });
    }

    const start = new Date(startDate);
    const end = new Date(endDate);

    // Fetch all entries in the date range
    const entries = await db.query.timeEntries.findMany({
      where: and(eq(timeEntries.userId, userId), gte(timeEntries.startedAt, start), lte(timeEntries.startedAt, end)),
      with: {
        client: true,
        request: true,
      },
    });

    // Calculate summary statistics
    const totalMinutes = entries.reduce((sum, entry) => sum + (entry.durationMinutes || 0), 0);
    const billableMinutes = entries.reduce(
      (sum, entry) => sum + (entry.isBillable ? entry.durationMinutes || 0 : 0),
      0,
    );
    const totalAmount = entries.reduce((sum, entry) => sum + parseFloat(entry.totalAmount || "0"), 0);

    // Group data based on groupBy parameter
    let groupedData: any[] = [];

    if (groupBy === "day") {
      const dayMap = new Map<string, any>();
      entries.forEach((entry) => {
        if (!entry.startedAt) return;
        const day = entry.startedAt.toISOString().split("T")[0];
        if (!dayMap.has(day)) {
          dayMap.set(day, {
            date: day,
            totalMinutes: 0,
            billableMinutes: 0,
            totalAmount: 0,
            entries: 0,
          });
        }
        const dayData = dayMap.get(day);
        dayData.totalMinutes += entry.durationMinutes || 0;
        if (entry.isBillable) {
          dayData.billableMinutes += entry.durationMinutes || 0;
        }
        dayData.totalAmount += parseFloat(entry.totalAmount || "0");
        dayData.entries += 1;
      });
      groupedData = Array.from(dayMap.values()).sort((a, b) => a.date.localeCompare(b.date));
    } else if (groupBy === "week") {
      const weekMap = new Map<string, any>();
      entries.forEach((entry) => {
        if (!entry.startedAt) return;
        const week = getWeekStart(entry.startedAt);
        if (!weekMap.has(week)) {
          weekMap.set(week, {
            week,
            totalMinutes: 0,
            billableMinutes: 0,
            totalAmount: 0,
            entries: 0,
          });
        }
        const weekData = weekMap.get(week);
        weekData.totalMinutes += entry.durationMinutes || 0;
        if (entry.isBillable) {
          weekData.billableMinutes += entry.durationMinutes || 0;
        }
        weekData.totalAmount += parseFloat(entry.totalAmount || "0");
        weekData.entries += 1;
      });
      groupedData = Array.from(weekMap.values()).sort((a, b) => a.week.localeCompare(b.week));
    } else if (groupBy === "month") {
      const monthMap = new Map<string, any>();
      entries.forEach((entry) => {
        if (!entry.startedAt) return;
        const month = entry.startedAt.toISOString().substring(0, 7); // YYYY-MM
        if (!monthMap.has(month)) {
          monthMap.set(month, {
            month,
            totalMinutes: 0,
            billableMinutes: 0,
            totalAmount: 0,
            entries: 0,
          });
        }
        const monthData = monthMap.get(month);
        monthData.totalMinutes += entry.durationMinutes || 0;
        if (entry.isBillable) {
          monthData.billableMinutes += entry.durationMinutes || 0;
        }
        monthData.totalAmount += parseFloat(entry.totalAmount || "0");
        monthData.entries += 1;
      });
      groupedData = Array.from(monthMap.values()).sort((a, b) => a.month.localeCompare(b.month));
    } else if (groupBy === "client") {
      const clientMap = new Map<string, any>();
      entries.forEach((entry) => {
        const clientId = entry.clientId || "no-client";
        const clientName = entry.client?.name || "No Client";
        if (!clientMap.has(clientId)) {
          clientMap.set(clientId, {
            clientId,
            clientName,
            totalMinutes: 0,
            billableMinutes: 0,
            totalAmount: 0,
            entries: 0,
          });
        }
        const clientData = clientMap.get(clientId);
        clientData.totalMinutes += entry.durationMinutes || 0;
        if (entry.isBillable) {
          clientData.billableMinutes += entry.durationMinutes || 0;
        }
        clientData.totalAmount += parseFloat(entry.totalAmount || "0");
        clientData.entries += 1;
      });
      groupedData = Array.from(clientMap.values()).sort((a, b) => b.totalMinutes - a.totalMinutes);
    } else if (groupBy === "request") {
      const requestMap = new Map<string, any>();
      entries.forEach((entry) => {
        const requestId = entry.requestId || "no-request";
        const requestTitle = entry.request?.title || "No Request";
        if (!requestMap.has(requestId)) {
          requestMap.set(requestId, {
            requestId,
            requestTitle,
            totalMinutes: 0,
            billableMinutes: 0,
            totalAmount: 0,
            entries: 0,
          });
        }
        const requestData = requestMap.get(requestId);
        requestData.totalMinutes += entry.durationMinutes || 0;
        if (entry.isBillable) {
          requestData.billableMinutes += entry.durationMinutes || 0;
        }
        requestData.totalAmount += parseFloat(entry.totalAmount || "0");
        requestData.entries += 1;
      });
      groupedData = Array.from(requestMap.values()).sort((a, b) => b.totalMinutes - a.totalMinutes);
    }

    return NextResponse.json({
      summary: {
        totalMinutes,
        totalHours: Math.round((totalMinutes / 60) * 100) / 100,
        billableMinutes,
        billableHours: Math.round((billableMinutes / 60) * 100) / 100,
        nonBillableMinutes: totalMinutes - billableMinutes,
        nonBillableHours: Math.round(((totalMinutes - billableMinutes) / 60) * 100) / 100,
        totalAmount: Math.round(totalAmount * 100) / 100,
        entriesCount: entries.length,
      },
      groupedData,
      entries,
    });
  } catch (error) {
    console.error("Error generating report:", error);
    return NextResponse.json({ error: "Failed to generate report" }, { status: 500 });
  }
}

/**
 * Helper function to get the Monday of the week for a given date
 */
function getWeekStart(date: Date): string {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
  d.setDate(diff);
  return d.toISOString().split("T")[0];
}
