# RLS Policies - Complete CRUD Coverage

## Issue Overview

**Problem**: Several tables had RLS enabled but incomplete policies, causing CRUD operations to fail.

**Impact**: 
- Creating invoices with line items would fail on `INSERT invoice_items`
- Editing/deleting comments would fail
- Deleting time entries would fail

**Root Cause**: Policies only covered SELECT operations, not INSERT/UPDATE/DELETE.

## Issues Fixed

### 1. invoice_items Table

**Before**:
- ✅ SELECT policy (view items)
- ❌ No INSERT policy
- ❌ No UPDATE policy
- ❌ No DELETE policy

**Problem**: Creating an invoice with line items:
```typescript
// This works
await supabase.from("invoices").insert({...});

// This FAILS with RLS error ❌
await supabase.from("invoice_items").insert([
  { invoice_id, description: "Item 1", ... }
]);
```

**After**:
- ✅ SELECT policy
- ✅ INSERT policy (can create items for client's invoices)
- ✅ UPDATE policy (can modify items for client's invoices)
- ✅ DELETE policy (can remove items for client's invoices)

**Result**: Invoice creation flows work end-to-end ✅

---

### 2. request_comments Table

**Before**:
- ✅ SELECT policy (view comments)
- ✅ INSERT policy (create comments)
- ❌ No UPDATE policy
- ❌ No DELETE policy

**Problem**: Users couldn't edit or delete their own comments.

**After**:
- ✅ SELECT policy
- ✅ INSERT policy
- ✅ UPDATE policy (can edit own comments only)
- ✅ DELETE policy (can delete own comments only)

**Security**: Users can only modify their own comments, not others'.

**Result**: Full comment management works ✅

---

### 3. time_entries Table

**Before**:
- ✅ SELECT policy (view entries)
- ✅ INSERT policy (create entries)
- ✅ UPDATE policy (edit entries)
- ❌ No DELETE policy

**Problem**: Users couldn't delete time entries even for corrections.

**After**:
- ✅ SELECT policy
- ✅ INSERT policy
- ✅ UPDATE policy
- ✅ DELETE policy (can delete own entries if not locked/billed)

**Security**: 
- Cannot delete locked entries
- Cannot delete billed entries
- Prevents accidental deletion of finalized time

**Result**: Time tracking corrections work ✅

---

## Complete Policy Matrix

### Migration 005 Tables:

| Table | SELECT | INSERT | UPDATE | DELETE | Policy Type |
|-------|--------|--------|--------|--------|-------------|
| invoices | ✅ | ✅ | ✅ | ✅ | FOR ALL (staff) |
| invoice_items | ✅ | ✅ | ✅ | ✅ | **FIXED!** |
| requests | ✅ | ✅ | ✅ | - | Intentional (no delete) |
| request_comments | ✅ | ✅ | ✅ | ✅ | **FIXED!** |
| time_entries | ✅ | ✅ | ✅ | ✅ | **FIXED!** |
| projects | ✅ | ✅ | ✅ | ✅ | FOR ALL (staff) |
| proposals | ✅ | ✅ | ✅ | ✅ | FOR ALL (staff) |

### Other Migrations:

All other tables reviewed and confirmed to have complete policies where needed.

---

## How RLS Policies Work

### Policy Types:

**FOR SELECT** - Who can read rows
```sql
CREATE POLICY "name" ON table
  FOR SELECT
  USING (condition);
```

**FOR INSERT** - Who can create rows
```sql
CREATE POLICY "name" ON table
  FOR INSERT
  WITH CHECK (condition);
```

**FOR UPDATE** - Who can modify rows
```sql
CREATE POLICY "name" ON table
  FOR UPDATE
  USING (condition);
```

**FOR DELETE** - Who can remove rows
```sql
CREATE POLICY "name" ON table
  FOR DELETE
  USING (condition);
```

**FOR ALL** - All operations (shorthand)
```sql
CREATE POLICY "name" ON table
  FOR ALL
  USING (condition);
```

### Best Practices:

1. **Enable RLS**: Always enable RLS on tables with sensitive data
2. **Complete Coverage**: Add policies for all needed operations
3. **Principle of Least Privilege**: Only grant minimum necessary access
4. **Test Both Paths**: Test both user access AND staff access
5. **Check Child Tables**: Ensure child tables have policies too

---

## Common RLS Patterns

### Pattern 1: Own Records Only

```sql
-- Users can only operate on their own records
CREATE POLICY "users_own_records" ON table
  FOR ALL
  USING (user_id = auth.uid());
```

### Pattern 2: Client Scope

```sql
-- Users can operate on their client's records
CREATE POLICY "client_scope" ON table
  FOR ALL
  USING (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );
```

### Pattern 3: Parent-Child Relationship

```sql
-- Can operate on child records if own parent record
CREATE POLICY "child_via_parent" ON child_table
  FOR ALL
  USING (
    parent_id IN (
      SELECT id FROM parent_table
      WHERE client_id IN (SELECT client_id FROM users WHERE id = auth.uid())
    )
  );
```

### Pattern 4: Role-Based Admin Access

```sql
-- Admins can do anything
CREATE POLICY "admin_access" ON table
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin')
    )
  );
```

---

## Testing RLS Policies

### Test INSERT:

```sql
-- As authenticated user
INSERT INTO invoice_items (invoice_id, description, amount)
VALUES ('invoice-uuid', 'Test Item', 100.00);

-- Should succeed if:
-- 1. Invoice belongs to user's client
-- 2. OR user is admin
```

### Test UPDATE:

```sql
-- As authenticated user
UPDATE request_comments
SET comment = 'Updated comment'
WHERE id = 'comment-uuid';

-- Should succeed if:
-- 1. Comment was created by this user
-- 2. OR user is admin
```

### Test DELETE:

```sql
-- As authenticated user
DELETE FROM time_entries
WHERE id = 'entry-uuid';

-- Should succeed if:
-- 1. Entry belongs to this user
-- 2. AND entry is not locked
-- 3. AND entry is not billed
-- 4. OR user is admin
```

---

## Verification Checklist

For each table with RLS:

- [ ] SELECT policy exists (who can view)
- [ ] INSERT policy exists (if users need to create)
- [ ] UPDATE policy exists (if users need to modify)
- [ ] DELETE policy exists (if users need to remove)
- [ ] Policies tested with actual data
- [ ] Edge cases handled (locked, billed, etc.)
- [ ] Admin override works
- [ ] Client isolation enforced

---

## Migration File Changes

### File: `lib/db/migrations/005_create_application_tables.sql`

**Added Policies**:

1. **invoice_items**:
   - `CREATE POLICY "Users can insert invoice items"`
   - `CREATE POLICY "Users can update invoice items"`
   - `CREATE POLICY "Users can delete invoice items"`

2. **request_comments**:
   - `CREATE POLICY "Users can update own comments"`
   - `CREATE POLICY "Users can delete own comments"`

3. **time_entries**:
   - `CREATE POLICY "Users can delete their own time entries"`

**Total**: 6 new policies added

---

## Impact on Application Flows

### Invoice Creation Flow:

**Before** (broken):
```typescript
// 1. Create invoice
const { data: invoice } = await supabase
  .from("invoices")
  .insert({ client_id, amount: 100 })
  .select()
  .single();

// 2. Add line items
const { error } = await supabase
  .from("invoice_items")
  .insert([
    { invoice_id: invoice.id, description: "Item 1", amount: 50 },
    { invoice_id: invoice.id, description: "Item 2", amount: 50 },
  ]);

// ❌ Error: new row violates row-level security policy for table "invoice_items"
```

**After** (working):
```typescript
// Same code as above
// ✅ Success! All items inserted
```

### Comment Edit Flow:

**Before** (broken):
```typescript
await supabase
  .from("request_comments")
  .update({ comment: "Updated text" })
  .eq("id", commentId);

// ❌ Error: row-level security policy violation
```

**After** (working):
```typescript
// ✅ Success! Comment updated (if own comment)
```

### Time Entry Deletion:

**Before** (broken):
```typescript
await supabase
  .from("time_entries")
  .delete()
  .eq("id", entryId);

// ❌ Error: row-level security policy violation
```

**After** (working):
```typescript
// ✅ Success! Entry deleted (if own entry and not locked/billed)
```

---

## Summary

**Tables Fixed**: 3 (invoice_items, request_comments, time_entries)  
**Policies Added**: 6 new RLS policies  
**Impact**: Critical CRUD operations now work  
**Security**: Maintained (proper access control)  

**Before**: Incomplete RLS causing operation failures  
**After**: Complete CRUD policy coverage ✅  

---

## Recommendations for Future Tables

When adding new tables with RLS:

1. **Always add all CRUD policies** (SELECT, INSERT, UPDATE, DELETE)
2. **Test each operation** after adding policies
3. **Consider child tables** (like invoice_items)
4. **Add business rules** (like locked/billed checks)
5. **Document policies** in migration comments

### Template for Complete RLS:

```sql
ALTER TABLE my_table ENABLE ROW LEVEL SECURITY;

-- SELECT
CREATE POLICY "users_select" ON my_table FOR SELECT
  USING (client_id = get_user_client_id());

-- INSERT  
CREATE POLICY "users_insert" ON my_table FOR INSERT
  WITH CHECK (client_id = get_user_client_id());

-- UPDATE
CREATE POLICY "users_update" ON my_table FOR UPDATE
  USING (client_id = get_user_client_id());

-- DELETE
CREATE POLICY "users_delete" ON my_table FOR DELETE
  USING (client_id = get_user_client_id());

-- Admin override
CREATE POLICY "admin_all" ON my_table FOR ALL
  USING (user_is_admin());
```

---

**Status**: ✅ All RLS policies complete and functional  
**Tested**: Invoice creation, comment editing, time entry deletion  
**Result**: No more RLS policy violations
