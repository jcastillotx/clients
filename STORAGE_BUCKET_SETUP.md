# Supabase Storage Bucket Setup for Support Ticket Attachments

## Required: Create Storage Bucket

You need to create a storage bucket in Supabase for ticket attachments.

### Steps to Create the Bucket

1. **Go to Supabase Dashboard**
   - Visit: https://app.supabase.com
   - Select your project

2. **Navigate to Storage**
   - Click **Storage** in the left sidebar

3. **Create New Bucket**
   - Click **New bucket** button
   - **Name**: `attachments`
   - **Public bucket**: ✅ Yes (check this box)
   - **File size limit**: 5MB
   - **Allowed MIME types**: `image/*` (or leave empty for all)
   - Click **Create bucket**

### Alternative: Create via SQL

Or run this SQL in Supabase SQL Editor:

```sql
-- Create the storage bucket
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'attachments',
  'attachments',
  true,
  5242880, -- 5MB in bytes
  ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp']
)
ON CONFLICT (id) DO NOTHING;

-- Create storage policies for authenticated users
CREATE POLICY "Authenticated users can upload attachments"
ON storage.objects FOR INSERT
TO authenticated
WITH CHECK (
  bucket_id = 'attachments' 
  AND (storage.foldername(name))[1] = 'support-tickets'
);

CREATE POLICY "Attachments are publicly accessible"
ON storage.objects FOR SELECT
TO public
USING (bucket_id = 'attachments');

CREATE POLICY "Users can delete their own attachments"
ON storage.objects FOR DELETE
TO authenticated
USING (
  bucket_id = 'attachments' 
  AND auth.uid()::text = (storage.foldername(name))[1]
);
```

## Bucket Configuration

- **Name**: `attachments`
- **Public**: Yes (files will have public URLs)
- **Path structure**: `support-tickets/{timestamp}-{random}.{ext}`
- **Max file size**: 5MB per file
- **Allowed types**: Images only (jpeg, png, gif, webp)

## Storage Policies

The bucket needs these policies:

1. **Upload**: Authenticated users can upload to `support-tickets/` folder
2. **Download**: Public read access (files are publicly accessible via URL)
3. **Delete**: Users can delete their own uploads

## Verify Setup

After creating the bucket, test the upload:

1. Go to `/support/new` in your app
2. Try uploading an image
3. Should see preview and be able to remove it
4. Create ticket and verify attachment URLs are saved

## File Organization

Files are organized as:
```
attachments/
└── support-tickets/
    ├── 1707998765432-a3f9x2.jpg
    ├── 1707998765433-b7k2m1.png
    └── ...
```

## Usage in App

The uploaded files are stored in the ticket's `metadata` field:

```json
{
  "metadata": {
    "attachments": [
      {
        "name": "screenshot.png",
        "url": "https://xxxxx.supabase.co/storage/v1/object/public/attachments/support-tickets/1707998765432-a3f9x2.png",
        "type": "image/png",
        "size": 245760
      }
    ]
  }
}
```

## Security Considerations

- ✅ File size limited to 5MB
- ✅ Only image types allowed
- ✅ Files stored in public bucket (read-only for all)
- ✅ Upload requires authentication
- ✅ Unique filenames prevent collisions
- ✅ No direct file deletion from UI (prevents abuse)

## Troubleshooting

### "Bucket not found" error
- Create the bucket in Supabase Dashboard → Storage
- Make sure it's named exactly `attachments`

### "Permission denied" on upload
- Check storage policies are created
- Verify user is authenticated
- Check bucket is set to public

### Images not displaying
- Verify bucket is public
- Check the public URL is correct
- Ensure CORS is enabled (Supabase does this by default)

---

**After creating the bucket, the upload feature will work immediately!**
