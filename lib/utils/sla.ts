/**
 * SLA configuration and utility functions
 */

/**
 * SLA targets in hours by priority
 */
export const SLA_TARGETS = {
  urgent: {
    response_hours: 1,
    resolution_hours: 4,
  },
  high: {
    response_hours: 4,
    resolution_hours: 24,
  },
  medium: {
    response_hours: 8,
    resolution_hours: 72,
  },
  low: {
    response_hours: 24,
    resolution_hours: 168, // 7 days
  },
} as const;

/**
 * Calculate SLA due dates based on priority
 */
export function calculateSlaDueDates(priority: keyof typeof SLA_TARGETS, createdAt: Date) {
  const targets = SLA_TARGETS[priority];

  const responseDue = new Date(createdAt);
  responseDue.setHours(responseDue.getHours() + targets.response_hours);

  const resolutionDue = new Date(createdAt);
  resolutionDue.setHours(resolutionDue.getHours() + targets.resolution_hours);

  return {
    slaResponseDueAt: responseDue,
    slaResolutionDueAt: resolutionDue,
  };
}

/**
 * Calculate percentage of SLA time used
 */
export function calculateSlaPercentUsed(createdAt: Date, dueAt: Date, pausedMinutes: number = 0): number {
  const now = new Date();
  const totalMinutes = (dueAt.getTime() - createdAt.getTime()) / (1000 * 60);

  if (totalMinutes <= 0) return 100;

  const elapsedMinutes = (now.getTime() - createdAt.getTime()) / (1000 * 60) - pausedMinutes;

  return Math.min(100, Math.max(0, (elapsedMinutes / totalMinutes) * 100));
}

/**
 * Get SLA status
 */
export function getSlaStatus(
  firstResponseAt: Date | null,
  slaResponseDueAt: Date | null,
  slaResolutionDueAt: Date | null,
  slaResponseBreached: boolean,
  slaResolutionBreached: boolean,
  slaPaused: boolean,
  status: string,
  createdAt: Date,
  slaPausedDurationMinutes: number = 0,
): {
  status: "on_track" | "warning" | "response_breached" | "breached" | "paused";
  responsePercentUsed: number;
  resolutionPercentUsed: number;
} {
  const WARNING_THRESHOLD = 75; // percentage

  if (slaResolutionBreached) {
    return {
      status: "breached",
      responsePercentUsed: 100,
      resolutionPercentUsed: 100,
    };
  }

  if (slaResponseBreached && !firstResponseAt) {
    return {
      status: "response_breached",
      responsePercentUsed: 100,
      resolutionPercentUsed: slaResolutionDueAt
        ? calculateSlaPercentUsed(createdAt, slaResolutionDueAt, slaPausedDurationMinutes)
        : 0,
    };
  }

  if (slaPaused) {
    return {
      status: "paused",
      responsePercentUsed:
        slaResponseDueAt && !firstResponseAt
          ? calculateSlaPercentUsed(createdAt, slaResponseDueAt, slaPausedDurationMinutes)
          : 0,
      resolutionPercentUsed: slaResolutionDueAt
        ? calculateSlaPercentUsed(createdAt, slaResolutionDueAt, slaPausedDurationMinutes)
        : 0,
    };
  }

  const responsePercentUsed =
    slaResponseDueAt && !firstResponseAt
      ? calculateSlaPercentUsed(createdAt, slaResponseDueAt, slaPausedDurationMinutes)
      : 0;

  const resolutionPercentUsed =
    slaResolutionDueAt && status !== "resolved" && status !== "closed"
      ? calculateSlaPercentUsed(createdAt, slaResolutionDueAt, slaPausedDurationMinutes)
      : 0;

  const isApproachingBreach = responsePercentUsed >= WARNING_THRESHOLD || resolutionPercentUsed >= WARNING_THRESHOLD;

  return {
    status: isApproachingBreach ? "warning" : "on_track",
    responsePercentUsed,
    resolutionPercentUsed,
  };
}

/**
 * Format time remaining
 */
export function formatTimeRemaining(dueAt: Date | null): string | null {
  if (!dueAt) return null;

  const now = new Date();
  const diff = dueAt.getTime() - now.getTime();

  if (diff <= 0) return "Breached";

  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) {
    return `${days}d ${hours % 24}h`;
  }
  if (hours > 0) {
    return `${hours}h ${minutes % 60}m`;
  }
  return `${minutes}m`;
}

/**
 * Get SLA status color for UI
 */
export function getSlaStatusColor(
  status: "on_track" | "warning" | "response_breached" | "breached" | "paused",
): string {
  const colors = {
    on_track: "text-green-600 bg-green-50 border-green-200",
    warning: "text-yellow-600 bg-yellow-50 border-yellow-200",
    response_breached: "text-orange-600 bg-orange-50 border-orange-200",
    breached: "text-red-600 bg-red-50 border-red-200",
    paused: "text-gray-600 bg-gray-50 border-gray-200",
  };

  return colors[status];
}
