/**
 * Cryptographically secure random password for UI generation (browser + Node).
 * Ensures at least one character from each class, then fills to length and shuffles.
 */
const LOWER = "abcdefghijklmnopqrstuvwxyz";
const UPPER = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const DIGITS = "0123456789";
const SYMBOLS = "!@#$%^&*-_=+";

function randomUint32(): number {
  const buf = new Uint32Array(1);
  crypto.getRandomValues(buf);
  return buf[0]!;
}

function randomCharFrom(set: string): string {
  return set[randomUint32() % set.length]!;
}

function shuffleString(chars: string[]): string {
  const a = [...chars];
  for (let i = a.length - 1; i > 0; i--) {
    const j = randomUint32() % (i + 1);
    const t = a[i];
    a[i] = a[j]!;
    a[j] = t!;
  }
  return a.join("");
}

export function generateSecurePassword(length = 16): string {
  const target = Math.max(12, Math.min(128, length));
  const all = LOWER + UPPER + DIGITS + SYMBOLS;
  const chars: string[] = [
    randomCharFrom(LOWER),
    randomCharFrom(UPPER),
    randomCharFrom(DIGITS),
    randomCharFrom(SYMBOLS),
  ];
  for (let i = chars.length; i < target; i++) {
    chars.push(randomCharFrom(all));
  }
  return shuffleString(chars);
}
