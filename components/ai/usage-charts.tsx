"use client";

import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

interface TokenDataPoint {
  date: string;
  tokens: number;
  cost: number;
}

interface UsageChartsProps {
  type: "tokens" | "costs" | "models" | "performance";
  data?: TokenDataPoint[];
}

export function UsageCharts({ type, data = [] }: UsageChartsProps) {
  if (type === "tokens") {
    if (data.length === 0 || data.every((d) => d.tokens === 0)) {
      return (
        <div className="flex items-center justify-center h-[350px] text-muted-foreground text-sm">
          No token usage data for this period.
        </div>
      );
    }

    return (
      <ResponsiveContainer width="100%" height={350}>
        <AreaChart data={data}>
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
                    <p className="text-sm text-muted-foreground">
                      Tokens: {(payload[0].value as number).toLocaleString()}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Cost: ${payload[0].payload.cost.toFixed(4)}
                    </p>
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

  // Placeholder for other chart types (costs, models, performance)
  return (
    <div className="flex items-center justify-center h-[350px] text-muted-foreground text-sm">
      Chart coming soon.
    </div>
  );
}
