# OAuth Credentials Setup Guide

This guide explains how to obtain OAuth credentials for Google and Microsoft Office integrations used in the Kre8iv Designs Client Portal.

---

## Google OAuth Setup

### 1. Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Select a project** → **New Project**
3. Enter project name: `Kre8iv Client Portal` (or your preferred name)
4. Click **Create**

### 2. Enable Required APIs

1. In the Google Cloud Console, go to **APIs & Services** → **Library**
2. Enable the following APIs:
   - **Google Drive API** (for file storage integration)
   - **Google Analytics Data API** (for analytics integration)
   - **Google Search Console API** (for SEO monitoring)
   - **Google Places API** (for review monitoring)

### 3. Configure OAuth Consent Screen

1. Go to **APIs & Services** → **OAuth consent screen**
2. Select **External** user type → Click **Create**
3. Fill in the required fields:
   - **App name**: `Kre8iv Client Portal`
   - **User support email**: Your support email
   - **Developer contact email**: Your email
4. Click **Save and Continue**
5. Add scopes (click **Add or Remove Scopes**):
   - `https://www.googleapis.com/auth/drive.file`
   - `https://www.googleapis.com/auth/analytics.readonly`
   - `https://www.googleapis.com/auth/webmasters.readonly`
6. Click **Save and Continue**
7. Add test users (your email addresses) → Click **Save and Continue**

### 4. Create OAuth Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Select **Application type**: **Web application**
4. Enter **Name**: `Kre8iv Client Portal - Web`
5. Add **Authorized redirect URIs**:

   ```
   https://yourdomain.com/storage/google-drive/oauth/callback
   https://yourdomain.com/oauth/analytics/callback
   https://yourdomain.com/oauth/search-console/callback
   ```

   **For local development**, also add:

   ```
   http://localhost:8000/storage/google-drive/oauth/callback
   http://localhost:8000/oauth/analytics/callback
   http://localhost:8000/oauth/search-console/callback
   ```

6. Click **Create**
7. Copy the **Client ID** and **Client Secret**

### 5. Add to .env File

Add the following to your `.env` file:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here

# Google Services
GOOGLE_PLACES_API_KEY=your-places-api-key
GOOGLE_ANALYTICS_PROPERTY_ID=your-property-id
```

---

## Microsoft Office 365 OAuth Setup

### 1. Register an Application in Azure

1. Go to [Azure Portal](https://portal.azure.com/)
2. Navigate to **Azure Active Directory** → **App registrations**
3. Click **New registration**
4. Fill in the details:
   - **Name**: `Kre8iv Client Portal`
   - **Supported account types**: Select **Accounts in any organizational directory and personal Microsoft accounts**
   - **Redirect URI**: Select **Web** and enter:
     ```
     https://yourdomain.com/storage/onedrive/oauth/callback
     ```
5. Click **Register**

### 2. Configure API Permissions

1. In your app registration, go to **API permissions**
2. Click **Add a permission** → **Microsoft Graph**
3. Select **Delegated permissions**
4. Add the following permissions:
   - `Files.ReadWrite` (for OneDrive file access)
   - `User.Read` (for user profile)
   - `offline_access` (for refresh tokens)
5. Click **Add permissions**
6. Click **Grant admin consent** (if you're an admin)

### 3. Create Client Secret

1. Go to **Certificates & secrets**
2. Click **New client secret**
3. Enter a description: `Client Portal Secret`
4. Select expiration: **24 months** (recommended)
5. Click **Add**
6. **IMPORTANT**: Copy the **Value** immediately (it won't be shown again)

### 4. Get Application IDs

1. Go to **Overview** page of your app registration
2. Copy the following:
   - **Application (client) ID**
   - **Directory (tenant) ID**

### 5. Configure Redirect URIs

1. Go to **Authentication**
2. Under **Platform configurations** → **Web**, add redirect URIs:

   **Production**:

   ```
   https://yourdomain.com/storage/onedrive/oauth/callback
   ```

   **Local Development**:

   ```
   http://localhost:8000/storage/onedrive/oauth/callback
   ```

3. Under **Implicit grant and hybrid flows**, enable:
   - ✅ **ID tokens**
4. Click **Save**

### 6. Add to .env File

Add the following to your `.env` file:

```env
# Microsoft OAuth
MICROSOFT_CLIENT_ID=your-application-client-id
MICROSOFT_CLIENT_SECRET=your-client-secret-value
MICROSOFT_TENANT_ID=your-tenant-id

# Or use 'common' for multi-tenant
# MICROSOFT_TENANT_ID=common
```

---

## Callback URLs Reference

Replace `yourdomain.com` with your actual domain. For local development, use `localhost:8000`.

### Google Services

| Service          | Callback URL                                                 |
| ---------------- | ------------------------------------------------------------ |
| Google Drive     | `https://yourdomain.com/storage/google-drive/oauth/callback` |
| Google Analytics | `https://yourdomain.com/oauth/analytics/callback`            |
| Search Console   | `https://yourdomain.com/oauth/search-console/callback`       |

### Microsoft Services

| Service  | Callback URL                                             |
| -------- | -------------------------------------------------------- |
| OneDrive | `https://yourdomain.com/storage/onedrive/oauth/callback` |

### Social Media OAuth (if needed)

| Platform  | Callback URL                                             |
| --------- | -------------------------------------------------------- |
| Facebook  | `https://yourdomain.com/oauth/social/facebook/callback`  |
| LinkedIn  | `https://yourdomain.com/oauth/social/linkedin/callback`  |
| Twitter/X | `https://yourdomain.com/oauth/social/twitter/callback`   |
| Instagram | `https://yourdomain.com/oauth/social/instagram/callback` |

---

## Testing OAuth Integration

### 1. Test Google Drive Connection

1. Log in to your client portal as an admin
2. Go to **Settings** → **Storage Integrations**
3. Click **Connect Google Drive**
4. You should be redirected to Google's consent screen
5. Grant permissions
6. You should be redirected back to your portal

### 2. Test Microsoft OneDrive Connection

1. Go to **Settings** → **Storage Integrations**
2. Click **Connect OneDrive**
3. Sign in with your Microsoft account
4. Grant permissions
5. You should be redirected back to your portal

---

## Troubleshooting

### Common Issues

**"Redirect URI mismatch" error**:

- Ensure the callback URL in your OAuth provider matches exactly (including http/https)
- Check for trailing slashes
- Verify the domain matches your `APP_URL` in `.env`

**"Invalid client" error**:

- Double-check your Client ID and Client Secret
- Ensure you copied the entire secret value
- Verify the credentials haven't expired

**"Access denied" error**:

- Check that required API permissions are granted
- For Microsoft, ensure admin consent is granted
- Verify the OAuth consent screen is configured correctly

**"Token expired" error**:

- The application will automatically refresh tokens
- If issues persist, disconnect and reconnect the integration

---

## Security Best Practices

1. **Never commit credentials to Git**
   - Keep `.env` file in `.gitignore`
   - Use `.env.example` as a template only

2. **Use different credentials for development and production**
   - Create separate OAuth apps for each environment
   - This prevents accidental data mixing

3. **Regularly rotate client secrets**
   - Microsoft secrets expire after 24 months
   - Set a reminder to rotate before expiration

4. **Monitor OAuth usage**
   - Check Google Cloud Console for API usage
   - Review Azure AD sign-in logs for suspicious activity

5. **Limit OAuth scopes**
   - Only request permissions your app actually needs
   - Review and remove unused scopes

---

## Additional Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Microsoft Identity Platform Documentation](https://docs.microsoft.com/en-us/azure/active-directory/develop/)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Azure Portal](https://portal.azure.com/)
- [Google API Library](https://console.cloud.google.com/apis/library)
- [Microsoft Graph Explorer](https://developer.microsoft.com/en-us/graph/graph-explorer)
