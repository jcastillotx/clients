"use client";

import { useState, useRef, FormEvent } from "react";
import { Send, Paperclip, X, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";

interface Attachment {
  file: File;
  preview?: string;
}

interface MessageComposerProps {
  conversationId: string;
  onMessageSent?: () => void;
  disabled?: boolean;
}

export function MessageComposer({ conversationId, onMessageSent, disabled = false }: MessageComposerProps) {
  const [message, setMessage] = useState("");
  const [attachments, setAttachments] = useState<Attachment[]>([]);
  const [sending, setSending] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    const newAttachments: Attachment[] = files.map((file) => ({
      file,
      preview: file.type.startsWith("image/") ? URL.createObjectURL(file) : undefined,
    }));
    setAttachments((prev) => [...prev, ...newAttachments]);
  };

  const removeAttachment = (index: number) => {
    setAttachments((prev) => {
      const updated = [...prev];
      if (updated[index].preview) {
        URL.revokeObjectURL(updated[index].preview!);
      }
      updated.splice(index, 1);
      return updated;
    });
  };

  const uploadAttachments = async () => {
    // In a real implementation, this would upload files to storage
    // and return the file metadata
    // For now, return mock data
    return attachments.map((att) => ({
      path: `uploads/${Date.now()}-${att.file.name}`,
      filename: att.file.name,
      mimeType: att.file.type,
      sizeBytes: att.file.size,
    }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!message.trim() && attachments.length === 0) return;
    if (sending || disabled) return;

    setSending(true);

    try {
      // Upload attachments if any
      const uploadedAttachments = attachments.length > 0 ? await uploadAttachments() : [];

      // Send message
      const response = await fetch("/api/messages", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          conversationId,
          messageBody: message.trim(),
          type: attachments.length > 0 ? "file" : "text",
          attachments: uploadedAttachments,
        }),
      });

      if (!response.ok) throw new Error("Failed to send message");

      // Clear form
      setMessage("");
      setAttachments([]);
      if (fileInputRef.current) fileInputRef.current.value = "";

      // Notify parent
      onMessageSent?.();

      // Focus back on textarea
      textareaRef.current?.focus();
    } catch (error) {
      console.error("Error sending message:", error);
    } finally {
      setSending(false);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e as unknown as FormEvent);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="border-t bg-background p-4">
      {/* Attachments Preview */}
      {attachments.length > 0 && (
        <div className="mb-3 flex flex-wrap gap-2">
          {attachments.map((attachment, index) => (
            <div key={index} className="relative group rounded-lg border bg-muted p-2 flex items-center gap-2">
              {attachment.preview ? (
                <img src={attachment.preview} alt={attachment.file.name} className="w-10 h-10 object-cover rounded" />
              ) : (
                <div className="w-10 h-10 bg-primary/10 rounded flex items-center justify-center">
                  <Paperclip className="w-5 h-5 text-primary" />
                </div>
              )}
              <div className="flex-1 min-w-0">
                <p className="text-xs font-medium truncate">{attachment.file.name}</p>
                <p className="text-xs text-muted-foreground">{(attachment.file.size / 1024).toFixed(1)} KB</p>
              </div>
              <button
                type="button"
                onClick={() => removeAttachment(index)}
                className="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-destructive text-destructive-foreground flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <X className="w-3 h-3" />
              </button>
            </div>
          ))}
        </div>
      )}

      {/* Input Area */}
      <div className="flex gap-2">
        {/* File Upload Button */}
        <Button
          type="button"
          variant="outline"
          size="icon"
          onClick={() => fileInputRef.current?.click()}
          disabled={disabled || sending}
          className="flex-shrink-0"
        >
          <Paperclip className="w-4 h-4" />
        </Button>
        <input
          ref={fileInputRef}
          type="file"
          multiple
          className="hidden"
          onChange={handleFileSelect}
          accept="image/*,.pdf,.doc,.docx,.txt"
        />

        {/* Message Input */}
        <Textarea
          ref={textareaRef}
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="Type a message... (Enter to send, Shift+Enter for new line)"
          disabled={disabled || sending}
          className="flex-1 min-h-[60px] max-h-[200px] resize-none"
          rows={2}
        />

        {/* Send Button */}
        <Button
          type="submit"
          size="icon"
          disabled={disabled || sending || (!message.trim() && attachments.length === 0)}
          className="flex-shrink-0"
        >
          {sending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
        </Button>
      </div>

      {/* Helper Text */}
      <p className="text-xs text-muted-foreground mt-2">Press Enter to send, Shift+Enter for new line</p>
    </form>
  );
}
