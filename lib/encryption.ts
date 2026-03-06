import { createCipheriv, createDecipheriv, randomBytes, scryptSync } from "crypto";

const ALGORITHM = "aes-256-gcm";
const IV_LENGTH = 16;
const TAG_LENGTH = 16;
const SALT_LENGTH = 32;
const KEY_LENGTH = 32;

function getEncryptionKey(): string {
  const key = process.env.ENCRYPTION_KEY;
  if (!key) {
    throw new Error(
      "ENCRYPTION_KEY environment variable is required for encrypted settings. " +
        "Generate one with: openssl rand -hex 32",
    );
  }
  return key;
}

function deriveKey(salt: Buffer): Buffer {
  const masterKey = getEncryptionKey();
  return scryptSync(masterKey, salt, KEY_LENGTH);
}

/**
 * Encrypt a plaintext value using AES-256-GCM with a derived key.
 * Returns a base64-encoded string containing: salt + iv + authTag + ciphertext.
 */
export function encrypt(plaintext: string): string {
  const salt = randomBytes(SALT_LENGTH);
  const key = deriveKey(salt);
  const iv = randomBytes(IV_LENGTH);

  const cipher = createCipheriv(ALGORITHM, key, iv);
  const encrypted = Buffer.concat([cipher.update(plaintext, "utf8"), cipher.final()]);
  const authTag = cipher.getAuthTag();

  // Pack: salt(32) + iv(16) + authTag(16) + ciphertext
  const packed = Buffer.concat([salt, iv, authTag, encrypted]);
  return packed.toString("base64");
}

/**
 * Decrypt a base64-encoded encrypted value produced by encrypt().
 */
export function decrypt(encryptedBase64: string): string {
  const packed = Buffer.from(encryptedBase64, "base64");

  const salt = packed.subarray(0, SALT_LENGTH);
  const iv = packed.subarray(SALT_LENGTH, SALT_LENGTH + IV_LENGTH);
  const authTag = packed.subarray(SALT_LENGTH + IV_LENGTH, SALT_LENGTH + IV_LENGTH + TAG_LENGTH);
  const ciphertext = packed.subarray(SALT_LENGTH + IV_LENGTH + TAG_LENGTH);

  const key = deriveKey(salt);
  const decipher = createDecipheriv(ALGORITHM, key, iv);
  decipher.setAuthTag(authTag);

  const decrypted = Buffer.concat([decipher.update(ciphertext), decipher.final()]);
  return decrypted.toString("utf8");
}

/**
 * Mask a secret value for display (e.g., "sk-ant-api03-...xyz")
 */
export function maskSecret(value: string): string {
  if (value.length <= 8) return "••••••••";
  const prefix = value.slice(0, 4);
  const suffix = value.slice(-4);
  return `${prefix}${"•".repeat(Math.min(value.length - 8, 20))}${suffix}`;
}
