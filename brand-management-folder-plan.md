# Brand Management Folder Implementation Plan

This plan details the implementation of an automatic "Brand Management" folder for clients, used to store branding materials (vector logos, letterhead, etc.) upon client creation.

## Objective
Automatically create a dedicated "Brand Management" document folder for every new client to organize their branding assets.

## Key Files & Context
- **Schema**: `lib/db/schema/documents.ts` (adding folder support)
- **API**: `app/api/clients/route.ts` (triggering the creation event)
- **Background Jobs**: `lib/inngest/functions/client-setup.ts` (new function)
- **Inngest Client**: `lib/inngest/client.ts` (event definition)
- **UI**: `components/documents/document-library.tsx` (displaying folders)

## Implementation Steps

### 1. Database Schema Update
- Add a `folders` table to `lib/db/schema/documents.ts` to represent logical folders.
- Add `folder_id` to the `documents` table as an optional foreign key.

```typescript
// Proposed addition to lib/db/schema/documents.ts
export const folders = pgTable("folders", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  clientId: uuid("client_id").notNull(),
  type: text("type"), // e.g., 'brand_management', 'general'
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
});
```

### 2. Inngest Event Definition
- Update `lib/inngest/client.ts` to include a `client.created` event.

```typescript
export interface ClientCreatedEvent {
  name: "client.created";
  data: {
    clientId: string;
    companyName: string;
  };
}
```

### 3. Trigger Event on Client Creation
- Modify `app/api/clients/route.ts` (POST) to send the `client.created` event after a successful insertion.

### 4. Background Job for Folder Setup
- Create `lib/inngest/functions/client-setup.ts`.
- This function will:
    - Listen for `client.created`.
    - Create a "Brand Management" folder in the `folders` table for that client.

### 5. UI Updates
- Update `DocumentLibrary` to fetch and display folders.
- Filter documents by `folder_id` when a folder is selected.

## Verification & Testing
- **Manual Test**: Create a new client via the Admin UI and verify the "Brand Management" folder appears in the Documents section for that client.
- **Unit Test**: Verify the Inngest function correctly inserts the folder record.
- **Integration Test**: Verify the POST `/api/clients` endpoint correctly triggers the Inngest event.
