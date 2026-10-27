import { createElement, type ComponentProps } from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { MessageBubble } from "@/components/messages/message-bubble";

type Message = ComponentProps<typeof MessageBubble>["message"];

const systemMessage: Message = {
  id: "notification-1",
  body: "A new project request was submitted: E2E verification.",
  type: "system",
  senderId: null,
  sender: null,
  createdAt: "2026-10-27T22:19:09Z",
  updatedAt: "2026-10-27T22:19:09Z",
  isPinned: false,
  attachments: null,
  isRead: false,
};

function renderMessage(message: Message) {
  return renderToStaticMarkup(createElement(MessageBubble, {
    message,
    isOwnMessage: false,
  }));
}

describe("MessageBubble sender rendering", () => {
  it("renders public system notifications without a sender", () => {
    const html = renderMessage(systemMessage);
    expect(html).toContain("System</span>");
    expect(html).toContain(systemMessage.body);
    expect(html).toContain(">S</span>");
  });

  it("keeps ordinary messages readable when their sender is missing", () => {
    const html = renderMessage({ ...systemMessage, type: "text" });
    expect(html).toContain("Unknown sender</span>");
    expect(html).toContain(systemMessage.body);
  });

  it("preserves named senders and their avatars", () => {
    const html = renderMessage({
      ...systemMessage,
      type: "text",
      senderId: "user-1",
      sender: {
        id: "user-1",
        name: "Test Reviewer",
        email: "reviewer@example.com",
        avatar: "https://example.com/avatar.png",
      },
    });
    expect(html).toContain("Test Reviewer</span>");
    expect(html).toContain('alt="Test Reviewer"');
    expect(html).toContain('src="https://example.com/avatar.png"');
  });
});
