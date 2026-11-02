import { createServer } from "node:net";
import { once } from "node:events";
import { expect, it } from "vitest";
import { sendViaSMTP } from "@/lib/email/providers";

it("delivers an application email with a buffer attachment through the patched SMTP client", async () => {
  let message = "";
  const server = createServer((socket) => {
    let pending = "";
    let receivingData = false;
    socket.write("220 localhost test SMTP\r\n");
    socket.on("data", (chunk) => {
      pending += chunk.toString();
      while (pending.includes("\r\n")) {
        const end = pending.indexOf("\r\n");
        const line = pending.slice(0, end);
        pending = pending.slice(end + 2);
        if (receivingData) {
          if (line === ".") {
            receivingData = false;
            socket.write("250 Message accepted\r\n");
          } else {
            message += line + "\r\n";
          }
        } else if (line.startsWith("EHLO") || line.startsWith("HELO")) {
          socket.write("250 localhost\r\n");
        } else if (line === "DATA") {
          receivingData = true;
          socket.write("354 Send message\r\n");
        } else if (line === "QUIT") {
          socket.end("221 Goodbye\r\n");
        } else {
          socket.write("250 OK\r\n");
        }
      }
    });
  });
  server.listen(0, "127.0.0.1");
  await once(server, "listening");
  const address = server.address();
  if (!address || typeof address === "string")
    throw new Error("Missing test listener");
  try {
    await sendViaSMTP(
      {
        provider: "smtp",
        smtp_host: "127.0.0.1",
        smtp_port: String(address.port),
        smtp_encryption: "none",
        from_email: "portal@example.invalid",
      },
      {
        to: "client@example.invalid",
        subject: "Delivery regression",
        text: "Client invitation",
        attachments: [
          {
            filename: "notice.txt",
            content: Buffer.from("Invitation attachment"),
          },
        ],
      },
    );
    expect(message).toContain("Subject: Delivery regression");
    expect(message).toContain("To: client@example.invalid");
    expect(message).toContain("Client invitation");
    expect(message).toContain("filename=notice.txt");
  } finally {
    await new Promise<void>((resolve, reject) =>
      server.close((error) => (error ? reject(error) : resolve())),
    );
  }
});
