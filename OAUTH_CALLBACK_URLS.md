# OAuth Callback URLs - Quick Reference

Replace `yourdomain.com` with your actual domain (e.g., `clients.kre8ivdesigns.com`).

For local development, use `http://localhost:8000` instead of `https://yourdomain.com`.

---

## Google Services

### Google Drive

```
https://yourdomain.com/storage/google/callback
```

### Google Analytics

```
https://yourdomain.com/oauth/analytics/google/callback
```

### Google Search Console

```
https://yourdomain.com/oauth/gsc/callback
```

---

## Microsoft Services

### OneDrive (Not currently implemented)

```
https://yourdomain.com/storage/onedrive/callback
```

_Note: OneDrive integration routes are not yet implemented in the application._

---

## Dropbox

```
https://yourdomain.com/storage/dropbox/callback
```

---

## Social Media Platforms

### Facebook

```
https://yourdomain.com/oauth/facebook/callback
```

### LinkedIn

```
https://yourdomain.com/oauth/linkedin/callback
```

### Twitter/X

```
https://yourdomain.com/oauth/twitter/callback
```

### Instagram

```
https://yourdomain.com/oauth/instagram/callback
```

### Pinterest

```
https://yourdomain.com/oauth/pinterest/callback
```

### TikTok

```
https://yourdomain.com/oauth/tiktok/callback
```

### Threads

```
https://yourdomain.com/oauth/threads/callback
```

---

## Local Development URLs

For testing locally, use these callback URLs:

### Google Drive

```
http://localhost:8000/storage/google/callback
```

### Google Analytics

```
http://localhost:8000/oauth/analytics/google/callback
```

### Google Search Console

```
http://localhost:8000/oauth/gsc/callback
```

### Dropbox

```
http://localhost:8000/storage/dropbox/callback
```

### Social Media (all platforms)

```
http://localhost:8000/oauth/{platform}/callback
```

Replace `{platform}` with: `facebook`, `linkedin`, `twitter`, `instagram`, `pinterest`, `tiktok`, or `threads`

---

## Important Notes

1. **Exact Match Required**: OAuth providers require exact URL matches, including:
   - Protocol (`http://` vs `https://`)
   - Domain name
   - Path (including trailing slashes)

2. **Multiple Environments**: Add both production and development URLs to your OAuth app configuration

3. **APP_URL Configuration**: Ensure your `.env` file has the correct `APP_URL`:

   ```env
   # Production
   APP_URL=https://clients.kre8ivdesigns.com

   # Local Development
   APP_URL=http://localhost:8000
   ```

4. **Testing**: Always test OAuth flows in both environments before going live
