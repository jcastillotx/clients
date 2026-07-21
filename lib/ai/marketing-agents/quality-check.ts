import type {
  QualityFinding,
  QualityReport,
} from "./types";

const NUMERIC_CLAIM_PATTERN =
  /(?:\b\d+(?:\.\d+)?%|\$\s?\d[\d,.]*|\b\d+(?:\.\d+)?x\b|\b(?:top|number)\s+#?\d+\b)/gi;
const SENTENCE_PATTERN = /[^.!?]+[.!?]+|[^.!?]+$/g;
const WORD_PATTERN = /[A-Za-z0-9][A-Za-z0-9'-]*/g;

interface QualityCheckInput {
  content: string;
  evidence?: string;
  voiceAvoid?: string;
}

function extractAvoidedPhrases(voiceAvoid: string): string[] {
  return voiceAvoid
    .split(/[\n,;]+/)
    .map((phrase) => phrase.trim().replace(/^[-*•]\s*/, ""))
    .filter((phrase) => phrase.length >= 2)
    .slice(0, 100);
}

function containsEvidenceForClaim(claim: string, evidence: string): boolean {
  const normalizedEvidence = evidence.toLowerCase();
  const normalizedClaim = claim.toLowerCase();

  if (normalizedEvidence.includes(normalizedClaim)) {
    return true;
  }

  const numbers = normalizedClaim.match(/\d+(?:\.\d+)?/g) ?? [];
  return numbers.length > 0 && numbers.every((number) => normalizedEvidence.includes(number));
}

export function runMarketingQualityCheck({
  content,
  evidence = "",
  voiceAvoid = "",
}: QualityCheckInput): QualityReport {
  const findings: QualityFinding[] = [];
  const lowerContent = content.toLowerCase();
  const avoidedPhrases = extractAvoidedPhrases(voiceAvoid);
  const matchedAvoidedPhrases = avoidedPhrases.filter((phrase) =>
    lowerContent.includes(phrase.toLowerCase()),
  );

  if (matchedAvoidedPhrases.length > 0) {
    findings.push({
      severity: "critical",
      category: "brand_voice",
      message: `Content uses prohibited or discouraged language: ${matchedAvoidedPhrases.join(", ")}.`,
      suggestion: "Replace the flagged language with terminology approved in the client brand guide.",
    });
  }

  const claims = Array.from(new Set(content.match(NUMERIC_CLAIM_PATTERN) ?? []));
  const unsupportedClaims = claims.filter(
    (claim) => !containsEvidenceForClaim(claim, evidence),
  );

  if (unsupportedClaims.length > 0) {
    findings.push({
      severity: "warning",
      category: "claims",
      message: `Potentially unsupported numeric claims found: ${unsupportedClaims.join(", ")}.`,
      suggestion: "Attach evidence for each claim or rewrite it without an unverified number or ranking.",
    });
  }

  const words = content.match(WORD_PATTERN) ?? [];
  const sentences = content.match(SENTENCE_PATTERN) ?? [];
  const averageSentenceLength =
    sentences.length > 0 ? words.length / sentences.length : words.length;

  if (averageSentenceLength > 28) {
    findings.push({
      severity: "warning",
      category: "readability",
      message: `Average sentence length is ${averageSentenceLength.toFixed(1)} words.`,
      suggestion: "Break longer sentences into shorter, single-purpose statements.",
    });
  }

  const letters = content.match(/[A-Za-z]/g) ?? [];
  const uppercaseLetters = content.match(/[A-Z]/g) ?? [];
  const uppercaseRatio =
    letters.length > 0 ? uppercaseLetters.length / letters.length : 0;
  const exclamationCount = (content.match(/!/g) ?? []).length;

  if (uppercaseRatio > 0.35 || exclamationCount > 4) {
    findings.push({
      severity: "warning",
      category: "formatting",
      message: "Content relies heavily on capital letters or repeated exclamation marks.",
      suggestion: "Use sentence case and restrained punctuation for a more credible presentation.",
    });
  }

  const criticalCount = findings.filter(
    (finding) => finding.severity === "critical",
  ).length;
  const warningCount = findings.filter(
    (finding) => finding.severity === "warning",
  ).length;
  const score = Math.max(0, 100 - criticalCount * 35 - warningCount * 12);
  const decision = criticalCount > 0 ? "BLOCKED" : warningCount > 0 ? "WARN" : "PASS";

  return {
    decision,
    score,
    findings,
    checks: {
      bannedLanguage: matchedAvoidedPhrases.length === 0,
      unsupportedClaims: unsupportedClaims.length === 0,
      readability: averageSentenceLength <= 28,
      formatting: uppercaseRatio <= 0.35 && exclamationCount <= 4,
    },
  };
}
