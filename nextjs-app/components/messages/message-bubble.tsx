"use client";

import { format } from "date-fns";
import { Check, CheckCheck, Paperclip, Pin } from "lucide-react";
import { cn } from "@/lib/utils";

interface Attachment {
  id: string;
  filename: string;
  mimeType: string | null;
  sizeBytes: number;
  path: string;
}

interface Sender {
  id: string;
  name: string;
  email: string;
  avatar: string | null;
}

interface Message {
  id: string;
  body: string | null;
  type: string;
  senderId: string;
  sender: Sender;
  createdAt: string;
  updatedAt: string;
  isPinned: boolean;
  attachments: Attachment[] | null;
  isRead: boolean;
}

interface MessageBubbleProps {
  message: Message;
  isOwnMessage: boolean;
  showSender?: boolean;
  onMarkRead?: (messageId: string) => void;
}

export function MessageBubble({ message, isOwnMessage, showSender = true, onMarkRead }: MessageBubbleProps) {
  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  return (
    <div className={cn("flex gap-3 mb-4", isOwnMessage ? "flex-row-reverse" : "flex-row")}>
      {/* Avatar */}
      {!isOwnMessage && (
        <div className="flex-shrink-0">
          {message.sender.avatar ? (
            <img src={message.sender.avatar} alt={message.sender.name} className="w-8 h-8 rounded-full" />
          ) : (
            <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
              <span className="text-xs font-semibold text-primary">{message.sender.name.charAt(0).toUpperCase()}</span>
            </div>
          )}
        </div>
      )}

      {/* Message Content */}
      <div className={cn("flex flex-col max-w-[70%]", isOwnMessage ? "items-end" : "items-start")}>
        {/* Sender Name */}
        {showSender && !isOwnMessage && (
          <span className="text-xs font-medium text-muted-foreground mb-1 px-1">{message.sender.name}</span>
        )}

        {/* Pinned Indicator */}
        {message.isPinned && (
          <div className="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 mb-1 px-1">
            <Pin className="w-3 h-3" />
            <span>Pinned</span>
          </div>
        )}

        {/* Message Bubble */}
        <div
          className={cn(
            "rounded-lg px-4 py-2 shadow-sm",
            isOwnMessage ? "bg-primary text-primary-foreground" : "bg-muted",
          )}
        >
          {/* Text Content */}
          {message.body && <p className="text-sm whitespace-pre-wrap break-words">{message.body}</p>}

          {/* Attachments */}
          {message.attachments && message.attachments.length > 0 && (
            <div className="mt-2 space-y-2">
              {message.attachments.map((attachment) => (
                <a
                  key={attachment.id}
                  href={`/api/attachments/${attachment.path}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={cn(
                    "flex items-center gap-2 p-2 rounded border",
                    isOwnMessage
                      ? "border-primary-foreground/20 hover:bg-primary-foreground/10"
                      : "border-border hover:bg-accent",
                  )}
                >
                  <Paperclip className="w-4 h-4 flex-shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium truncate">{attachment.filename}</p>
                    <p className="text-xs opacity-70">{formatFileSize(attachment.sizeBytes)}</p>
                  </div>
                </a>
              ))}
            </div>
          )}
        </div>

        {/* Timestamp and Status */}
        <div className={cn("flex items-center gap-1 mt-1 px-1", isOwnMessage ? "flex-row-reverse" : "flex-row")}>
          <span className="text-xs text-muted-foreground">{format(new Date(message.createdAt), "h:mm a")}</span>

          {/* Read Receipt for own messages */}
          {isOwnMessage && (
            <div className="text-muted-foreground">
              {message.isRead ? <CheckCheck className="w-3 h-3" /> : <Check className="w-3 h-3" />}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
