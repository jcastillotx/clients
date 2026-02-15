# ✨ Image Upload Feature for Support Tickets

## What Was Added

Image upload functionality has been added to the support ticket creation form at `/support/new`.

## Features

### 1. **Multiple Image Upload**
- Upload multiple images at once
- Support for common image formats (JPEG, PNG, GIF, WebP)
- Maximum file size: 5MB per image

### 2. **Image Preview Grid**
- See thumbnails of uploaded images
- Responsive grid layout (2 columns on mobile, 3 on desktop)
- Hover to show remove button

### 3. **File Management**
- Remove unwanted images before submitting
- Visual feedback during upload
- File size and name displayed

### 4. **Storage Integration**
- Files uploaded to Supabase Storage
- Stored in `attachments/support-tickets/` folder
- Public URLs generated automatically
- Secure, unique filenames

## How to Use

### For Users:

1. **Go to** `/support/new` (Create Support Ticket page)

2. **Fill in ticket details**:
   - Subject
   - Description
   - Category
   - Priority

3. **Upload images** (optional):
   - Click "Choose Files" or drag and drop
   - Select one or more images
   - Maximum 5MB per file
   - See preview immediately

4. **Remove images** (if needed):
   - Hover over an image preview
   - Click the "Remove" button

5. **Submit ticket**:
   - Click "Create Ticket"
   - Images are automatically attached

### For Developers:

The uploaded files are stored in the ticket's `metadata` field:

```typescript
{
  metadata: {
    attachments: [
      {
        name: "screenshot.png",
        url: "https://xxxxx.supabase.co/storage/v1/object/public/attachments/support-tickets/1707998765432-a3f9x2.png",
        type: "image/png",
        size: 245760
      }
    ]
  }
}
```

## Setup Required

### 🚨 Important: Create Supabase Storage Bucket

Before using this feature, you **must create** the storage bucket:

**Quick Setup:**

1. Go to: https://app.supabase.com
2. Select your project
3. Navigate to: **Storage** (left sidebar)
4. Click: **New bucket**
5. Settings:
   - **Name**: `attachments`
   - **Public bucket**: ✅ Yes
   - **File size limit**: 5MB
6. Click: **Create bucket**

**Or via SQL** (run in Supabase SQL Editor):

```sql
INSERT INTO storage.buckets (id, name, public, file_size_limit)
VALUES ('attachments', 'attachments', true, 5242880)
ON CONFLICT (id) DO NOTHING;

-- Add storage policies
CREATE POLICY "Authenticated users can upload"
ON storage.objects FOR INSERT TO authenticated
WITH CHECK (bucket_id = 'attachments');

CREATE POLICY "Public read access"
ON storage.objects FOR SELECT TO public
USING (bucket_id = 'attachments');
```

## UI/UX Details

### File Input
```tsx
<Input
  type="file"
  accept="image/*"
  multiple
  onChange={handleFileUpload}
  disabled={isUploading}
/>
```

### Image Preview Grid
```tsx
<div className="grid grid-cols-2 md:grid-cols-3 gap-4">
  {uploadedFiles.map((file, index) => (
    <div className="relative group border rounded-lg overflow-hidden">
      <img src={file.url} alt={file.name} className="w-full h-32 object-cover" />
      {/* Hover overlay with remove button */}
    </div>
  ))}
</div>
```

## Validation

### File Type
- ✅ Only image files accepted (`image/*`)
- ❌ Non-image files rejected with error toast

### File Size
- ✅ Max 5MB per file
- ❌ Larger files rejected with error toast

### Upload Limits
- No hard limit on number of files
- Practical limit based on storage quota

## Storage Structure

Files are organized in Supabase Storage:

```
attachments/
└── support-tickets/
    ├── 1707998765432-a3f9x2.jpg  ← Ticket 1 attachment
    ├── 1707998765433-b7k2m1.png  ← Ticket 1 attachment
    ├── 1707998877654-c8n5p3.jpg  ← Ticket 2 attachment
    └── ...
```

Filename format: `{timestamp}-{random}.{extension}`

## Security

### Upload Security
- ✅ Requires authentication
- ✅ File type validation (client + server)
- ✅ File size validation
- ✅ Unique filenames prevent collisions

### Access Control
- ✅ Public read (anyone with URL can view)
- ✅ Authenticated upload only
- ✅ Files stored in isolated folder

### Privacy Considerations
- Files are **publicly accessible** via URL
- Don't upload sensitive/private images
- URLs are long and hard to guess
- Consider adding access control if needed

## Error Handling

### Upload Errors
```typescript
// File too large
toast.error("screenshot.png is too large (max 5MB)")

// Wrong file type
toast.error("document.pdf is not an image file")

// Upload failed
toast.error("Failed to upload screenshot.png")
```

### Success Messages
```typescript
// Upload complete
toast.success("3 file(s) uploaded successfully")

// Ticket created
toast.success("Support ticket created successfully")
```

## Displaying Attachments

To display attachments on the ticket detail page, add this component:

```tsx
{ticket.metadata?.attachments && (
  <div className="mt-6">
    <h3 className="text-lg font-semibold mb-3">Attachments</h3>
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      {ticket.metadata.attachments.map((file: any, index: number) => (
        <a
          key={index}
          href={file.url}
          target="_blank"
          rel="noopener noreferrer"
          className="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
        >
          <img
            src={file.url}
            alt={file.name}
            className="w-full h-32 object-cover"
          />
          <div className="p-2 bg-muted">
            <p className="text-xs truncate" title={file.name}>
              {file.name}
            </p>
          </div>
        </a>
      ))}
    </div>
  </div>
)}
```

## Testing

### Test Checklist

- [ ] Upload single image
- [ ] Upload multiple images
- [ ] Remove image before submit
- [ ] Try to upload non-image file (should fail)
- [ ] Try to upload file > 5MB (should fail)
- [ ] Create ticket with attachments
- [ ] Verify attachments appear in ticket
- [ ] Click attachment to view full size
- [ ] Test on mobile device
- [ ] Test with slow internet connection

### Test Files

Good test files:
- ✅ Small JPEG (< 1MB)
- ✅ PNG screenshot
- ✅ Animated GIF
- ✅ WebP image

Should fail:
- ❌ PDF document
- ❌ Large image (> 5MB)
- ❌ Video file
- ❌ ZIP file

## Future Enhancements

Potential improvements:

1. **Drag & Drop Upload**
   - Visual drop zone
   - Drag files from desktop

2. **Image Compression**
   - Compress large images before upload
   - Reduce storage costs

3. **More File Types**
   - PDFs for documentation
   - Videos for screen recordings

4. **Private Attachments**
   - RLS policies for private files
   - Only visible to ticket owner + staff

5. **Attachment Management**
   - Delete attachments after ticket created
   - Replace/update attachments

6. **Image Editing**
   - Crop/resize before upload
   - Add annotations/arrows

## Troubleshooting

### "Failed to upload" error

**Cause**: Storage bucket doesn't exist
**Solution**: Create `attachments` bucket in Supabase

### "Permission denied" error

**Cause**: Missing storage policies
**Solution**: Run the SQL policies from STORAGE_BUCKET_SETUP.md

### Images not showing

**Cause**: Bucket not public
**Solution**: Enable public access on `attachments` bucket

### Slow uploads

**Cause**: Large file size
**Solution**: Compress images before upload or reduce file size limit

---

## Summary

**What**: Image upload for support tickets  
**Where**: `/support/new` page  
**Storage**: Supabase Storage (`attachments` bucket)  
**Limits**: 5MB per file, images only  
**Access**: Public URLs (anyone with link)  

**Next Step**: Create the `attachments` bucket in Supabase! See [STORAGE_BUCKET_SETUP.md](STORAGE_BUCKET_SETUP.md)
