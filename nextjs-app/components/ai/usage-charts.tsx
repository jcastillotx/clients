"use client";

import { useMemo } from "react";
import {
  AreaChart,
  Area,
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts";

interface UsageChartsProps {
  type: "tokens" | "costs" | "models" | "performance";
}

export function UsageCharts({ type }: UsageChartsProps) {
  const tokenData = useMemo(
    () => [
      { date: "Jan 1", tokens: 45000, cost: 23.45 },
      { date: "Jan 2", tokens: 52000, cost: 27.12 },
      { date: "Jan 3", tokens: 48000, cost: 25.18 },
      { date: "Jan 4", tokens: 61000, cost: 31.89 },
      { date: "Jan 5", tokens: 55000, cost: 28.76 },
      { date: "Jan 6", tokens: 49000, cost: 25.63 },
      { date: "Jan 7", tokens: 58000, cost: 30.34 },
    ],
    [],
  );

  const modelData = useMemo(
    () => [
      { model: "GPT-4", avgResponseTime: 1.2, successRate: 98.5, cost: 123.45 },
      { model: "Claude 3", avgResponseTime: 1.1, successRate: 99.2, cost: 89.23 },
      { model: "GPT-3.5", avgResponseTime: 0.8, successRate: 97.8, cost: 21.88 },
      { model: "Gemini Pro", avgResponseTime: 1.3, successRate: 96.5, cost: 45.67 },
    ],
    [],
  );

  const performanceData = useMemo(
    () => [
      { hour: "00:00", responseTime: 1.1, requests: 45 },
      { hour: "04:00", responseTime: 0.9, requests: 23 },
      { hour: "08:00", responseTime: 1.3, requests: 156 },
      { hour: "12:00", responseTime: 1.5, requests: 234 },
      { hour: "16:00", responseTime: 1.4, requests: 198 },
      { hour: "20:00", responseTime: 1.2, requests: 123 },
    ],
    [],
  );

  const costData = useMemo(
    () => [
      { month: "Aug", projected: 180, actual: 165 },
      { month: "Sep", projected: 195, actual: 178 },
      { month: "Oct", projected: 210, actual: 203 },
      { month: "Nov", projected: 225, actual: 218 },
      { month: "Dec", projected: 240, actual: 231 },
      { month: "Jan", projected: 255, actual: 234 },
      { month: "Feb", projected: 270, actual: null },
    ],
    [],
  );

  if (type === "tokens") {
    return (
      <ResponsiveContainer width="100%" height={350}>
        <AreaChart data={tokenData}>
          <defs>
            <linearGradient id="colorTokens" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#8884d8" stopOpacity={0.8} />
              <stop offset="95%" stopColor="#8884d8" stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis />
          <Tooltip
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                return (
                  <div className="bg-background border rounded-lg p-3 shadow-lg">
                    <p className="font-medium">{payload[0].payload.date}</p>
                    <p className="text-sm text-muted-foreground">Tokens: {payload[0].value?.toLocaleString()}</p>
                    <p className="text-sm text-muted-foreground">Cost: ${payload[0].payload.cost.toFixed(2)}</p>
                  </div>
                );
              }
              return null;
            }}
          />
          <Area type="monotone" dataKey="tokens" stroke="#8884d8" fillOpacity={1} fill="url(#colorTokens)" />
        </AreaChart>
      </ResponsiveContainer>
    );
  }

  if (type === "costs") {
    return (
      <ResponsiveContainer width="100%" height={350}>
        <LineChart data={costData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="month" />
          <YAxis />
          <Tooltip
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                return (
                  <div className="bg-background border rounded-lg p-3 shadow-lg">
                    <p className="font-medium">{payload[0].payload.month}</p>
                    <p className="text-sm text-blue-500">Projected: ${payload[0].value}</p>
                    {payload[1]?.value && <p className="text-sm text-green-500">Actual: ${payload[1].value}</p>}
                  </div>
                );
              }
              return null;
            }}
          />
          <Legend />
          <Line type="monotone" dataKey="projected" stroke="#3b82f6" strokeDasharray="5 5" name="Projected" />
          <Line type="monotone" dataKey="actual" stroke="#10b981" strokeWidth={2} name="Actual" />
        </LineChart>
      </ResponsiveContainer>
    );
  }

  if (type === "models") {
    return (
      <ResponsiveContainer width="100%" height={350}>
        <BarChart data={modelData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="model" />
          <YAxis yAxisId="left" orientation="left" stroke="#8884d8" />
          <YAxis yAxisId="right" orientation="right" stroke="#82ca9d" />
          <Tooltip
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                return (
                  <div className="bg-background border rounded-lg p-3 shadow-lg">
                    <p className="font-medium mb-2">{payload[0].payload.model}</p>
                    <p className="text-sm text-muted-foreground">Avg Response: {payload[0].payload.avgResponseTime}s</p>
                    <p className="text-sm text-muted-foreground">Success Rate: {payload[0].payload.successRate}%</p>
                    <p className="text-sm text-muted-foreground">Cost: ${payload[0].payload.cost}</p>
                  </div>
                );
              }
              return null;
            }}
          />
          <Legend />
          <Bar yAxisId="left" dataKey="successRate" fill="#8884d8" name="Success Rate %" />
          <Bar yAxisId="right" dataKey="avgResponseTime" fill="#82ca9d" name="Avg Response (s)" />
        </BarChart>
      </ResponsiveContainer>
    );
  }

  if (type === "performance") {
    return (
      <ResponsiveContainer width="100%" height={350}>
        <AreaChart data={performanceData}>
          <defs>
            <linearGradient id="colorRequests" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#82ca9d" stopOpacity={0.8} />
              <stop offset="95%" stopColor="#82ca9d" stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="hour" />
          <YAxis yAxisId="left" />
          <YAxis yAxisId="right" orientation="right" />
          <Tooltip
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                return (
                  <div className="bg-background border rounded-lg p-3 shadow-lg">
                    <p className="font-medium">{payload[0].payload.hour}</p>
                    <p className="text-sm text-muted-foreground">Response Time: {payload[0].payload.responseTime}s</p>
                    <p className="text-sm text-muted-foreground">Requests: {payload[0].payload.requests}</p>
                  </div>
                );
              }
              return null;
            }}
          />
          <Legend />
          <Area
            yAxisId="left"
            type="monotone"
            dataKey="responseTime"
            stroke="#8884d8"
            fill="#8884d8"
            name="Response Time (s)"
          />
          <Area
            yAxisId="right"
            type="monotone"
            dataKey="requests"
            stroke="#82ca9d"
            fillOpacity={1}
            fill="url(#colorRequests)"
            name="Requests"
          />
        </AreaChart>
      </ResponsiveContainer>
    );
  }

  return null;
}
