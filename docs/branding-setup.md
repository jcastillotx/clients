# Kre8ivDesigns Marketing - Client Portal Branding Setup

This guide explains how to customize the client portal to match Kre8ivDesigns Marketing's brand identity.

## Quick Start

The portal is pre-configured with sensible defaults, but you can easily customize it to match your exact brand colors and style from https://www.kre8ivdesigns.com.

### Step 1: Extract Your Brand Colors

Visit your website and identify:

1. **Primary Brand Color**: The main color used in your logo and headers
2. **Secondary Color**: Accent or complementary color
3. **Dark Variant**: Darker version of primary (for hover states)
4. **Light Variant**: Lighter version of primary (for backgrounds)

You can use browser dev tools to inspect colors:
- Right-click on an element → Inspect
- Look for `background-color` or `color` in the Styles panel
- Colors will be in hex format like `#2563eb`

### Step 2: Update .env File

Copy `.env.example` to `.env` if you haven't already:
```bash
cp .env.example .env
```

Update the branding variables in `.env`:

```bash
# Your main brand color (from logo/headers)
BRAND_COLOR_PRIMARY="#YOUR_PRIMARY_COLOR"

# Darker version (usually 20-30% darker)
BRAND_COLOR_PRIMARY_DARK="#YOUR_PRIMARY_DARK"

# Lighter version (usually 80-90% lighter, for backgrounds)
BRAND_COLOR_PRIMARY_LIGHT="#YOUR_PRIMARY_LIGHT"

# Secondary/accent color
BRAND_COLOR_SECONDARY="#YOUR_SECONDARY_COLOR"

# Accent color for call-to-action buttons
BRAND_COLOR_ACCENT="#YOUR_ACCENT_COLOR"
```

### Step 3: Add Your Logo

Place your logo files in the `public/images/` directory:

1. **Main Logo** (`logo.png`): Used in the portal header (light background)
   - Recommended size: 200x50px or maintain aspect ratio
   - Format: PNG with transparent background preferred

2. **Dark Logo** (`logo-white.png`): White version for dark backgrounds
   - Same size as main logo
   - White or light-colored version

3. **Icon** (`icon.png`): Small logo/icon for mobile
   - Square, recommended: 192x192px or 512x512px
   - Used for app icons and favicons

4. **Favicon** (`favicon.ico`): Browser tab icon
   - 16x16px, 32x32px, and 48x48px in .ico format

Example:
```bash
# Create images directory if it doesn't exist
mkdir -p public/images

# Copy your logo files
cp /path/to/your/logo.png public/images/logo.png
cp /path/to/your/logo-white.png public/images/logo-white.png
cp /path/to/your/icon.png public/images/icon.png
cp /path/to/your/favicon.ico public/favicon.ico
```

Update `.env` with your logo dimensions:
```bash
BRAND_LOGO_WIDTH=200
BRAND_LOGO_HEIGHT=50
```

### Step 4: Configure Typography

If your website uses specific fonts, add them:

```bash
# Example for Google Fonts
BRAND_GOOGLE_FONTS="Montserrat:400,600,700"
BRAND_FONT_PRIMARY="Montserrat, sans-serif"
BRAND_FONT_SECONDARY="Montserrat, sans-serif"
```

### Step 5: Clear Cache & Test

```bash
# Clear configuration cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Visit the portal in your browser
```

## Advanced Customization

### Custom Gradients

Enable gradients for a more modern look:

```bash
BRAND_USE_GRADIENTS=true
BRAND_GRADIENT_PRIMARY="linear-gradient(135deg, #2563eb 0%, #3b82f6 100%)"
BRAND_GRADIENT_ACCENT="linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)"
```

### Button Styles

Choose button style:
- `rounded`: Modern rounded corners (default)
- `square`: Sharp corners
- `pill`: Fully rounded (pill-shaped)

```bash
BRAND_BUTTON_STYLE="rounded"
```

### Login Page Customization

Customize the login page background:

```bash
# Gradient background (default)
BRAND_AUTH_BG_STYLE="gradient"

# Solid color
BRAND_AUTH_BG_STYLE="solid"
BRAND_AUTH_BG_COLOR="#f3f4f6"

# Background image
BRAND_AUTH_BG_STYLE="image"
BRAND_AUTH_BG_IMAGE="images/login-bg.jpg"
```

### Design Elements

Adjust border radius for the entire portal:

```bash
# Small radius (subtle rounded corners)
BRAND_BORDER_RADIUS="0.25rem"

# Medium radius (default)
BRAND_BORDER_RADIUS="0.5rem"

# Large radius (very rounded)
BRAND_BORDER_RADIUS="1rem"
```

## How It Works

### Automatic CSS Generation

The system automatically generates a `public/css/brand.css` file from your configuration:

1. `BrandingServiceProvider` reads `config/branding.php`
2. Generates CSS with your brand colors as CSS custom properties
3. Applies overrides to AdminLTE and Tailwind components
4. Layouts automatically include the generated CSS

### CSS Custom Properties

The generated CSS creates these variables you can use anywhere:

```css
/* In any custom CSS or inline styles */
.my-element {
    color: var(--brand-primary);
    background: var(--brand-bg-alt);
    border-radius: var(--brand-radius);
}
```

### Blade Variables

All branding config is available in views:

```blade
{{ config('branding.company.name') }}
{{ config('branding.colors.primary') }}
{{ config('branding.logo.main') }}
```

## Common Customizations

### Match Website Header Color

If your website header is blue (#2563eb):
```bash
BRAND_COLOR_PRIMARY="#2563eb"
BRAND_COLOR_PRIMARY_DARK="#1e40af"
```

### Match Website Accent Color

If your website uses orange accents (#f97316):
```bash
BRAND_COLOR_ACCENT="#f97316"
```

### Custom Company Info

Update footer and contact information:
```bash
BRAND_COMPANY_NAME="Kre8ivDesigns Marketing"
BRAND_PHONE="210-549-7907"
BRAND_EMAIL="info@kre8ivdesigns.com"
BRAND_SUPPORT_EMAIL="support@kre8ivdesigns.com"
```

### Add Social Media Links

```bash
BRAND_SOCIAL_FACEBOOK="https://facebook.com/kre8ivdesigns"
BRAND_SOCIAL_LINKEDIN="https://linkedin.com/company/kre8ivdesigns"
BRAND_SOCIAL_INSTAGRAM="https://instagram.com/kre8ivdesigns"
```

## Troubleshooting

### Colors Not Updating

1. Clear config cache: `php artisan config:clear`
2. Clear view cache: `php artisan view:clear`
3. Hard refresh browser (Cmd+Shift+R or Ctrl+F5)
4. Check that the CSS file was regenerated: `ls -l public/css/brand.css`

### Logo Not Showing

1. Verify file exists: `ls -l public/images/logo.png`
2. Check file permissions: `chmod 644 public/images/*.png`
3. Verify .env path is correct (relative to public/ directory)
4. Check browser console for 404 errors

### Fonts Not Loading

1. Verify Google Fonts string format: `Family:weights` (e.g., `Inter:300,400,500,600,700`)
2. Check network tab in browser to see if font is loading
3. Try a different font family to test

## Manual CSS Overrides

For advanced customization, create custom CSS in `resources/css/custom.css`:

```css
/* Custom brand styles */
.my-custom-header {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
    padding: 2rem;
}

/* Override specific components */
.main-header .navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

Then include it in your layout:
```blade
@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom.css') }}">
@endpush
```

## Pro Tips

1. **Use a Color Picker**: Browser extensions like "ColorZilla" make it easy to grab colors from your website
2. **Test in Incognito**: Always test branding changes in an incognito window to avoid cached styles
3. **Mobile First**: Check how your branding looks on mobile devices
4. **Accessibility**: Ensure sufficient contrast between text and backgrounds (WCAG 2.1 AA standard)
5. **Consistency**: Use the same colors, fonts, and logo as your main website for brand consistency

## Getting Your Brand Colors

### Method 1: Browser Dev Tools

1. Visit https://www.kre8ivdesigns.com
2. Right-click on the header or logo → Inspect
3. Look for `background-color` or `color` in Styles panel
4. Copy the hex code (e.g., `#2563eb`)

### Method 2: ColorZilla Extension

1. Install ColorZilla (Chrome/Firefox extension)
2. Click the eyedropper icon
3. Click on any color on your website
4. Copy the hex code

### Method 3: Ask Your Designer

If you have brand guidelines or a designer, they should provide:
- Primary color
- Secondary color
- Text colors
- Logo files in various formats

## Need Help?

If you need assistance with branding customization:

1. Check this documentation first
2. Review `config/branding.php` for all available options
3. Contact support at: {{ config('branding.company.support_email') }}

---

**Last Updated**: 2025-12-27
