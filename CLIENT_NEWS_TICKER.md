# 📰 Client News Ticker Feature

## Overview

Clients see a **news ticker** in the top bar instead of the admin/staff controls. The ticker displays announcements, updates, and important information in an auto-rotating banner.

## Visual Design

### Client Top Bar

```
┌─────────────────────────────────────────────────────────────────┐
│ 📣 Welcome! Welcome to your client dashboard ● ● ○              │
└─────────────────────────────────────────────────────────────────┘
```

**Features:**
- Pulsing megaphone icon
- Title in primary color (bold)
- Content in muted color
- Navigation dots (active = filled, inactive = outline)
- Gradient background
- Auto-rotates every 5 seconds

### Comparison: Client vs Staff/Admin

**Clients See:**
```
📣 New Features: Check out our new time tracking tools ● ○ ○
```

**Staff/Admin See:**
```
👑 Admin Dashboard | John Doe | 🟢 Operational | ⏱️ 01:23:45 [Stop]
```

## How It Works

### Data Flow

1. **On Page Load:**
   - Fetches announcements from database
   - Filters by client_id (client-specific + global)
   - Only shows active, non-expired announcements
   - Sorts by priority (high to low)

2. **Auto-Rotation:**
   - Changes to next announcement every 5 seconds
   - Smooth fade transition
   - Loops back to first after last

3. **Manual Navigation:**
   - Click dots to jump to specific announcement
   - Resets auto-rotation timer

### Database Schema

**Table**: `announcements`

```sql
CREATE TABLE announcements (
  id UUID PRIMARY KEY,
  
  -- Content
  title TEXT NOT NULL,              -- "New Features"
  content TEXT NOT NULL,             -- "Check out our new tools..."
  link_url TEXT,                     -- Optional link
  link_text TEXT,                    -- Link button text
  
  -- Targeting
  client_id UUID,                    -- NULL = global, specific = client-only
  
  -- Status
  is_active BOOLEAN DEFAULT true,    -- Show/hide
  priority INTEGER DEFAULT 0,        -- Sort order (higher = first)
  
  -- Scheduling
  starts_at TIMESTAMPTZ,             -- When to start showing
  expires_at TIMESTAMPTZ,            -- When to stop showing
  
  -- Audit
  created_by UUID,
  created_at TIMESTAMPTZ,
  updated_at TIMESTAMPTZ,
  deleted_at TIMESTAMPTZ
);
```

### Query Logic

```typescript
// Fetch announcements for client
const { data } = await supabase
  .from("announcements")
  .select("*")
  .or(`client_id.eq.${clientId},client_id.is.null`) // Client-specific OR global
  .eq("is_active", true)
  .order("priority", { ascending: false })
  .order("created_at", { ascending: false })
  .limit(10);
```

**Returns:**
- Global announcements (client_id = NULL)
- Client-specific announcements (client_id = user's client)
- Only active announcements
- Sorted by priority, then date

## Creating Announcements

### Option 1: Via SQL (Quick)

```sql
-- Global announcement (shown to all clients)
INSERT INTO public.announcements (title, content, is_active, priority)
VALUES ('Welcome!', 'Welcome to your dashboard', true, 10);

-- Client-specific announcement
INSERT INTO public.announcements (title, content, client_id, is_active, priority)
VALUES (
  'New Invoice Available', 
  'Your monthly invoice is ready for review',
  'client-uuid-here',
  true,
  5
);

-- Scheduled announcement
INSERT INTO public.announcements (
  title, content, is_active, priority,
  starts_at, expires_at
)
VALUES (
  'Maintenance Window',
  'Scheduled maintenance on Feb 20th, 2-4 AM EST',
  true,
  8,
  '2026-02-18 00:00:00+00',
  '2026-02-21 00:00:00+00'
);
```

### Option 2: Via Admin UI (Future Feature)

Create an admin page at `/admin/announcements`:

```
┌─────────────────────────────────────────┐
│ Create Announcement                     │
├─────────────────────────────────────────┤
│ Title: [                              ] │
│ Content: [                            ] │
│ Target: [● All Clients  ○ Specific   ] │
│ Priority: [Medium ▼]                    │
│ Schedule: [○ Now  ● Scheduled]         │
│   Starts: [2026-02-20 09:00]           │
│   Expires: [2026-02-25 17:00]          │
│                                         │
│ [Create Announcement]                   │
└─────────────────────────────────────────┘
```

## Default Announcements

When no announcements exist, shows default messages:

1. **"Welcome!"** - Welcome to your client dashboard
2. **"Need Help?"** - Click 'Support' to create a ticket
3. **"View Invoices"** - Check your invoices and payment history

## Animation & Transitions

### Fade In/Out

```tsx
<div className="animate-in fade-in slide-in-from-right-2 duration-500">
  {currentNews.title}: {currentNews.content}
</div>
```

**Timing:**
- Fade out: 200ms
- Slide in from right: 300ms
- Total transition: 500ms
- Auto-rotation: Every 5 seconds

### Navigation Dots

```tsx
{newsItems.map((_, index) => (
  <button
    className={index === currentIndex 
      ? "w-6 bg-primary"        // Active: Wide filled dot
      : "w-1.5 bg-muted"        // Inactive: Small outlined dot
    }
  />
))}
```

## Responsive Design

**Desktop (≥768px):**
```
📣 New Features: Check out our new time tracking tools ● ● ○
[Full message visible]
```

**Mobile (<768px):**
```
📣 New Features: Check out...
[Truncated with dots]
```

## Use Cases

### 1. System Updates
```sql
INSERT INTO announcements (title, content, priority)
VALUES ('System Maintenance', 'Scheduled downtime tonight 2-4 AM', 10);
```

### 2. New Features
```sql
INSERT INTO announcements (title, content, link_url, link_text)
VALUES (
  'New Feature!', 
  'Time tracking is now available',
  '/time',
  'Try it now'
);
```

### 3. Client-Specific Notices
```sql
INSERT INTO announcements (title, content, client_id, priority)
VALUES (
  'Invoice Ready',
  'Your January invoice is ready for payment',
  'client-uuid',
  8
);
```

### 4. Temporary Alerts
```sql
INSERT INTO announcements (
  title, content, priority,
  starts_at, expires_at
)
VALUES (
  'Holiday Hours',
  'Office closed Dec 24-26',
  9,
  '2026-12-20 00:00:00',
  '2026-12-27 00:00:00'
);
```

## Advanced Features

### Click-through Links (Optional)

Add clickable links to announcements:

```tsx
{currentNews.link_url && (
  <Button 
    variant="link" 
    size="sm"
    onClick={() => router.push(currentNews.link_url)}
  >
    {currentNews.link_text || 'Learn More'}
  </Button>
)}
```

### Read Tracking (Optional)

Track which announcements clients have seen:

```sql
CREATE TABLE announcement_views (
  announcement_id UUID REFERENCES announcements(id),
  user_id UUID,
  viewed_at TIMESTAMPTZ DEFAULT NOW(),
  PRIMARY KEY (announcement_id, user_id)
);
```

### Pause on Hover (Optional)

```tsx
const [isPaused, setIsPaused] = useState(false);

<div 
  onMouseEnter={() => setIsPaused(true)}
  onMouseLeave={() => setIsPaused(false)}
>
  {/* News content */}
</div>

// Update interval logic
useEffect(() => {
  if (isPaused || newsItems.length <= 1) return;
  // ... rotation logic
}, [isPaused, newsItems]);
```

## Management Interface

### Announcements Admin Page (Future)

Create `/admin/announcements` page with:

- **List View**: See all announcements
- **Create**: Add new announcements
- **Edit**: Modify existing
- **Schedule**: Set start/end dates
- **Preview**: See how it looks to clients
- **Analytics**: View counts, click rates

## Customization

### Change Rotation Speed

```tsx
// In ClientNewsTicker component
const interval = setInterval(() => {
  setCurrentIndex((prev) => (prev + 1) % newsItems.length);
}, 8000); // Change from 5000 to 8000 for 8 seconds
```

### Change Animation Style

```tsx
// Slide from bottom
<div className="animate-in fade-in slide-in-from-bottom-2">

// Zoom in
<div className="animate-in fade-in zoom-in-95">

// No animation (instant)
<div>
```

### Custom Styling

```tsx
// Change gradient colors
<div className="bg-gradient-to-r from-blue-500/10 via-background to-purple-500/10">

// Add border accent
<div className="border-b-2 border-primary/20">

// Larger text
<span className="text-lg font-bold">
```

## Testing

### Test Checklist:

- [ ] Login as client user
- [ ] See news ticker in top bar
- [ ] Ticker shows announcements
- [ ] Auto-rotates every 5 seconds
- [ ] Can click dots to navigate manually
- [ ] Shows global announcements
- [ ] Shows client-specific announcements (if any)
- [ ] Responsive on mobile
- [ ] Animations smooth
- [ ] No console errors

### Test Data:

```sql
-- Create test announcements
INSERT INTO public.announcements (title, content, is_active, priority) VALUES
  ('Test 1', 'This is the first announcement', true, 10),
  ('Test 2', 'This is the second announcement', true, 5),
  ('Test 3', 'This is the third announcement', true, 0);

-- Verify they appear
SELECT * FROM announcements ORDER BY priority DESC;
```

## Migration Instructions

### Run the migration:

```bash
# Automated
pnpm db:migrate

# Or via Supabase SQL Editor
# Copy contents of lib/db/migrations/006_create_announcements_table.sql
# Paste and run
```

### Verify table created:

```sql
SELECT table_name 
FROM information_schema.tables 
WHERE table_name = 'announcements';

SELECT * FROM announcements LIMIT 5;
```

## Summary

**For Clients:**
- 📰 News ticker with rotating announcements
- 📣 System updates and important notices
- 🎯 Auto-rotation every 5 seconds
- 📍 Manual navigation with dots
- 🎨 Smooth animations

**For Admin/Staff:**
- 👑 Role badge
- 🟢 System status
- ⏱️ Time tracking timer
- 📊 Full dashboard controls

**Status**: ✅ Complete - News ticker active for clients, full top bar for staff/admin

---

**Next**: Run `pnpm db:migrate` to create the announcements table, then test with both client and admin accounts!
