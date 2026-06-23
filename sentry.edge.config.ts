import * as Sentry from "@sentry/nextjs";
import { getSentryOptions } from "@/lib/sentry/config";

Sentry.init(getSentryOptions());
