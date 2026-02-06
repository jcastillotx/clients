# Internal Messaging System Documentation

## Overview

This is a complete internal messaging system built for the Next.js application, migrated from the Laravel backend. The system provides real-time chat functionality with support for conversations, message threads, file attachments, read receipts, and typing indicators.

## Architecture

### Database Schema (Drizzle ORM)

Located in `/lib/db/schema/messages.ts`

#### Tables

1. **conversations**
    - Primary table for message threads
    - Fields: id, clientId, title, contextType, contextId, isClosed, lastMessageAt
    - Supports linking to requests/projects via contextType/contextId
    - Indexed on: clientId+isClosed, clientId+context, clientId+lastMessageAt

2. **conversation_participants**
    - Pivot table linking users to conversations
    - Fields: id, conversationId, userId, role (client|staff)
    - Unique constraint on conversationId+userId

3. **messages**
    - Individual messages within conversations
    - Fields: id, conversationId, senderId, body, type (text|file|system)
    - Supports: meta (JSON for edit history), mentions, pinning
    - Indexed on: conversationId+createdAt

4. **message_reads**
    - Read receipt tracking
    - Fields: id, messageId, userId, readAt
    - Unique constraint on messageId+userId

5. **message_attachments**
    - File attachments for messages
    - Fields: id, messageId, disk, path, filename, mimeType, sizeBytes

### API Routes

#### GET /api/messages/conversations

- Lists all conversations for the authenticated user
- Returns: conversations with last message, unread count, participants
- Uses optimized SQL with subqueries for performance

#### POST /api/messages/conversations

- Creates a new conversation
- Body: { clientId, title?, participantIds, contextType?, contextId? }
- Auto-adds current user to participants

#### GET /api/messages?conversationId={id}

- Retrieves messages for a conversation
- Supports pagination: limit, offset
- Returns: messages with sender info, attachments, read status

#### POST /api/messages

- Sends a new message
- Body: { conversationId, messageBody, type?, attachments? }
- Updates conversation.lastMessageAt
- Supports file attachments

#### POST /api/messages/[id]/read

- Marks a message as read
- Creates message_reads entry with readAt timestamp

#### DELETE /api/messages/[id]/read

- Removes read status (for testing/undo)

### React Components

#### ConversationList

- Location: `/components/messages/conversation-list.tsx`
- Displays list of conversations in sidebar
- Shows: avatar, title, last message preview, timestamp, unread badge
- Auto-generates title from participants if not set
- Highlights unread conversations

#### MessageBubble

- Location: `/components/messages/message-bubble.tsx`
- Renders individual message with appropriate styling
- Features:
    - Different styles for own vs other messages
    - Sender avatar and name
    - Pinned message indicator
    - File attachment display with download links
    - Read receipts (single/double check marks)
    - Timestamp formatting

#### MessageComposer

- Location: `/components/messages/message-composer.tsx`
- Message input with file upload
- Features:
    - Multi-line text input (Shift+Enter for new line)
    - File attachment preview with remove option
    - Supports images, PDFs, documents
    - Attachment size display
    - Loading state during send
    - Auto-focus after send

#### ChatPane

- Location: `/components/messages/chat-pane.tsx`
- Main chat interface combining messages and composer
- Features:
    - Real-time message updates via Supabase Realtime
    - Auto-scroll to bottom on new messages
    - Auto-mark messages as read
    - Typing indicators
    - Empty state display
    - Loading state

#### MessagesPage

- Location: `/app/(dashboard)/messages/page.tsx`
- Full-page messaging interface
- Features:
    - Split layout: conversation list + chat pane
    - Responsive mobile design with toggle sidebar
    - Empty state with CTA
    - New conversation button

## Real-time Features

### Message Updates

Uses Supabase Realtime to subscribe to:

```typescript
channel: `conversation:${conversationId}`
event: INSERT on messages table
filter: conversation_id=eq.{conversationId}
```

When a new message is inserted:

1. Fetch full message with sender info
2. Add to local state
3. Auto-mark as read if not sent by current user
4. Scroll to bottom

### Typing Indicators

Uses Supabase Presence:

```typescript
channel: `typing:${conversationId}`;
event: presence.sync;
```

Tracks which users are typing in real-time.

## Features

### Core Messaging

- Send text messages
- Upload file attachments (images, PDFs, documents)
- Real-time message delivery
- Message threading in conversations

### Conversation Management

- Create conversations with multiple participants
- Link conversations to requests or projects
- Auto-generated conversation titles
- Last message timestamp tracking
- Close/reopen conversations

### Read Receipts

- Single check mark: message sent
- Double check mark: message read
- Auto-mark messages as read when viewing
- Track read status per user

### File Attachments

- Support for multiple file types
- Image preview in message bubble
- File size display
- Download functionality

### User Experience

- Typing indicators
- Auto-scroll to new messages
- Unread message badges
- Mobile-responsive design
- Empty states with helpful CTAs

### Security

- Authentication required for all endpoints
- Conversation access verification
- User must be participant to view messages
- File access control

## Usage Examples

### Create a New Conversation

```typescript
const response = await fetch("/api/messages/conversations", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        clientId: "uuid",
        title: "Project Discussion",
        participantIds: ["user-uuid-1", "user-uuid-2"],
        contextType: "project",
        contextId: "project-uuid",
    }),
});
```

### Send a Message

```typescript
const response = await fetch("/api/messages", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        conversationId: "conversation-uuid",
        messageBody: "Hello, how are you?",
        type: "text",
        attachments: [],
    }),
});
```

### Send a Message with Attachments

```typescript
// First upload files, then send message
const uploadedFiles = await uploadToStorage(files);

const response = await fetch("/api/messages", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        conversationId: "conversation-uuid",
        messageBody: "Check out these files",
        type: "file",
        attachments: uploadedFiles.map((file) => ({
            path: file.path,
            filename: file.name,
            mimeType: file.type,
            sizeBytes: file.size,
        })),
    }),
});
```

## Future Enhancements

### Planned Features

1. **Message Editing** - Edit sent messages with history
2. **Message Reactions** - Add emoji reactions to messages
3. **Message Threading** - Reply to specific messages
4. **Search** - Full-text search across messages
5. **Mentions** - @mention users with notifications
6. **Message Pinning** - Pin important messages (UI already supports)
7. **Voice Messages** - Record and send audio
8. **Video Calls** - Integrate video calling
9. **File Preview** - In-app preview for images/PDFs
10. **Export Conversations** - Download conversation history

### Performance Optimizations

1. **Message Pagination** - Infinite scroll for old messages
2. **Image Optimization** - Compress and resize images
3. **Caching** - Cache conversation list and recent messages
4. **Database Indexes** - Additional indexes for common queries
5. **Connection Pooling** - Optimize database connections

### Administration

1. **Message Moderation** - Flag/delete inappropriate content
2. **Conversation Analytics** - Track message volume and response times
3. **Bulk Operations** - Archive/delete multiple conversations
4. **Message Retention** - Auto-delete old messages (GDPR)

## Migration Notes

### From Laravel to Next.js

The system maintains schema compatibility with the Laravel backend:

**Laravel Models → Drizzle Schema:**

- `Conversation` → `conversations`
- `Message` → `messages`
- `MessageRead` → `message_reads`
- `MessageAttachment` → `message_attachments`
- `conversation_participants` pivot table

**Key Differences:**

1. Laravel uses `bigint` IDs, Next.js uses `uuid`
2. Laravel stores attachments with `disk` field, preserved in Next.js
3. Laravel's `meta` JSON field preserved for edit history
4. Laravel's `mentions` array preserved for future @mentions feature

## Testing

### Manual Testing Checklist

- [ ] Create new conversation
- [ ] Send text message
- [ ] Upload file attachment
- [ ] View messages in conversation
- [ ] Mark messages as read
- [ ] Real-time message delivery
- [ ] Typing indicators
- [ ] Unread badge updates
- [ ] Mobile responsive layout
- [ ] Conversation list sorting

### Unit Tests (To be added)

- API route handlers
- Message validation
- Read receipt logic
- File upload handling

## Deployment

### Environment Variables

```env
DATABASE_URL=postgresql://...
NEXT_PUBLIC_SUPABASE_URL=https://...
NEXT_PUBLIC_SUPABASE_ANON_KEY=...
SUPABASE_SERVICE_ROLE_KEY=...
```

### Database Migration

Run Drizzle migration to create tables:

```bash
npm run db:push
# or
npm run db:migrate
```

### Supabase Realtime Setup

Ensure Realtime is enabled for:

- `messages` table
- `message_reads` table

### File Storage

Configure file storage for attachments:

- Supabase Storage bucket: `attachments`
- Public read access with RLS policies
- File size limits (recommend 10MB)

## Support

For issues or questions:

1. Check this documentation
2. Review API route implementations
3. Check component source code
4. Test with browser dev tools

## License

Internal use only - Client Management System
