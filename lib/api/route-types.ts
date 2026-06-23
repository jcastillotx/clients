/** Aggregated time report bucket used by GET /api/time-tracking/reports */
export type TimeReportBucket = {
  date?: string;
  week?: string;
  month?: string;
  clientId?: string;
  clientName?: string;
  requestId?: string;
  requestTitle?: string;
  totalMinutes: number;
  billableMinutes: number;
  totalAmount: number;
  entries: number;
};

export type AdMetricsInput = {
  impressions?: number;
  clicks?: number;
  spend?: number;
  conversions?: number;
  ctr?: number;
  cpc?: number;
  cpm?: number;
  roas?: number;
  videoViews?: number;
  videoViewsP25?: number;
  videoViewsP50?: number;
  videoViewsP75?: number;
  videoViewsP100?: number;
  linkClicks?: number;
  postEngagement?: number;
  reach?: number;
  frequency?: number;
};
