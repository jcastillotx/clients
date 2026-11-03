"use client";

import { useChat as useSdkChat } from "@ai-sdk/react";
import { TextStreamChatTransport } from "ai";
import { useMemo, useState, type ChangeEvent, type FormEvent } from "react";

/** Keep the portal's text-only API contract while using the maintained SDK. */
export function useChat({ api, id }: { api: string; id?: string }) {
  const [input, setInput] = useState("");
  const transport = useMemo(
    () =>
      new TextStreamChatTransport({
        api,
        prepareSendMessagesRequest: ({ messages }) => ({
          body: {
            messages: messages.map((message) => ({
              role: message.role,
              content: message.parts
                .filter((part) => part.type === "text")
                .map((part) => part.text)
                .join(""),
            })),
          },
        }),
      }),
    [api],
  );
  const chat = useSdkChat({ id, transport });
  const isLoading = chat.status === "submitted" || chat.status === "streaming";
  return {
    messages: chat.messages.map((message) => ({
      id: message.id,
      role: message.role,
      content: message.parts
        .filter((part) => part.type === "text")
        .map((part) => part.text)
        .join(""),
    })),
    input,
    setInput,
    handleInputChange: (
      event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
    ) => setInput(event.target.value),
    handleSubmit: (event?: FormEvent) => {
      event?.preventDefault();
      if (!input.trim() || isLoading) return;
      const text = input;
      setInput("");
      void chat.sendMessage({ text }).catch(() => {
        setInput(text);
      });
    },
    isLoading,
    error: chat.error,
    reload: () => chat.regenerate(),
  };
}
