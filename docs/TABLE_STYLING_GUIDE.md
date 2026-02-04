# Table Styling Guide - Tailwind CSS

## Overview

All tables in the portal are now styled exclusively with Tailwind CSS utilities. This guide provides the standard patterns and classes to use when creating or updating tables.

## Basic Table Structure

### Minimal Table
```html
<table class="table">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Data 1</td>
            <td>Data 2</td>
        </tr>
    </tbody>
</table>
```

### Responsive Table (Recommended)
Always wrap tables in a responsive container to prevent layout issues on mobile:

```html
<div class="table-responsive">
    <table class="table table-hover">
        <!-- table content -->
    </table>
</div>
```

## Available Table Classes

### Core Classes

#### `.table`
Base table class - **required on all tables**
- Sets width to 100%
- Applies proper text sizing and alignment
- Includes proper header and body styling

#### `.table-responsive`
Wrapper class for mobile-friendly tables
- Enables horizontal scrolling on small screens
- Apply to the parent `<div>` element
- **Recommended for all tables**

### Interactive Classes

#### `.table-hover`
Adds hover effect to rows
- Light gray background on hover in light mode
- Dark slate background in dark mode
- Use for interactive data tables

#### `.table-striped`
Alternating row colors for better readability
- Odd rows: white (light mode) / slate-900 (dark mode)
- Even rows: slate-50 (light mode) / slate-800 (dark mode)
- Good for long data lists

### Size Variants

#### `.table-sm`
Compact table with reduced padding
- Headers: `px-3 py-2` with smaller text
- Body cells: `px-3 py-2` with smaller text
- Use for dashboards or when space is limited

### Alignment

#### `.table-vcenter`
Vertically centers content in cells
- Applies `align-middle` to all cells
- Use when cells contain varied content heights

### Other Utilities

#### `.table-bordered`
Adds borders around cells
- Border color: slate-200 (light) / slate-700 (dark)
- Includes rounded corners with overflow hidden
- Use sparingly - modern design typically avoids heavy borders

#### `.table-light`
Light header background variant
- Background: slate-100
- Use for subtle header distinction

#### `.card-table`
Removes bottom margin when table is inside a card
- Apply to `<table>` element
- Use when embedding table in `.card-body`

#### `.table-active`
Highlights a specific row
- Background: blue-50 (light) / slate-800 (dark)
- Apply to `<tr>` element for selected rows

## Column Width Control

To prevent column compression, use Tailwind width utilities on `<th>` elements:

```html
<thead>
    <tr>
        <th class="min-w-48">Title</th>        <!-- Minimum 192px -->
        <th class="min-w-28">Type</th>         <!-- Minimum 112px -->
        <th class="w-24">Actions</th>          <!-- Fixed 96px -->
    </tr>
</thead>
```

### Common Width Classes
- `min-w-20` (80px) - Icons, small buttons
- `min-w-28` (112px) - Short labels, types
- `min-w-44` (176px) - Dates, timestamps
- `min-w-48` (192px) - Titles, names
- `w-24` (96px) - Fixed width for action columns

## Cell Content Styling

### Text Utilities
```html
<td class="font-medium">Bold text</td>
<td class="text-slate-600">Muted text</td>
<td class="capitalize">lowercase → Capitalized</td>
<td class="text-center">Centered</td>
```

### Status Badges
```html
<td>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        Active
    </span>
</td>
```

Badge color variants:
- Green: `bg-green-100 text-green-800` - Success, Active, Completed
- Blue: `bg-blue-100 text-blue-800` - Info, In Progress
- Yellow: `bg-yellow-100 text-yellow-800` - Warning, Pending
- Red: `bg-red-100 text-red-800` - Error, Failed, Cancelled
- Slate: `bg-slate-100 text-slate-800` - Neutral, Default

### Links
```html
<td>
    <a href="#" class="text-blue-600 hover:text-blue-800 hover:underline">
        View Details
    </a>
</td>
```

### Empty State
```html
<tbody>
    @if($items->isEmpty())
        <tr>
            <td colspan="5" class="text-center text-slate-500 py-8">
                No items found.
            </td>
        </tr>
    @endif
</tbody>
```

## Common Patterns

### Standard Data Table
```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data List</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="min-w-48">Name</th>
                        <th class="min-w-28">Status</th>
                        <th class="min-w-44">Created</th>
                        <th class="w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->name }}</td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="text-slate-600">{{ $item->created_at->format('M d, Y') }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                    @if($items->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center text-slate-500 py-8">
                                No items found.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
```

### Compact Dashboard Table
```html
<div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
        <thead>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Users</td>
                <td class="font-medium">1,234</td>
            </tr>
            <tr>
                <td>Active Sessions</td>
                <td class="font-medium">56</td>
            </tr>
        </tbody>
    </table>
</div>
```

## Dark Mode Support

All table styles automatically adapt to dark mode when `data-theme="dark"` is set on a parent element. No additional classes needed.

### Color Reference

| Element | Light Mode | Dark Mode |
|---------|------------|-----------|
| Header background | slate-50 | slate-800 |
| Header text | slate-700 | slate-300 |
| Body background | white | slate-900 |
| Body text | slate-900 | slate-200 |
| Row dividers | slate-200 | slate-700 |
| Hover background | slate-50 | slate-800 |

## Migration from Bootstrap

When updating existing tables from Bootstrap to Tailwind:

### Class Replacements

| Bootstrap Class | Tailwind Replacement | Notes |
|----------------|---------------------|-------|
| `table` | `table` | Keep as-is |
| `table-responsive` | `table-responsive` | Keep as-is (on wrapper div) |
| `table-striped` | `table-striped` | Keep as-is |
| `table-hover` | `table-hover` | Keep as-is |
| `table-sm` | `table-sm` | Keep as-is |
| `table-bordered` | `table-bordered` | Keep as-is |
| `table-vcenter` | `table-vcenter` | Keep as-is |
| `badge badge-*` | Custom badge span | See "Status Badges" section |
| `text-muted` | `text-slate-600` | Direct replacement |
| `mb-0` | `mb-0` | Keep as-is |

### Example Migration

**Before (Bootstrap):**
```html
<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>John Doe</td>
            <td><span class="badge badge-success">Active</span></td>
        </tr>
    </tbody>
</table>
```

**After (Tailwind):**
```html
<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th class="min-w-48">Name</th>
                <th class="min-w-28">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-medium">John Doe</td>
                <td>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

## Best Practices

1. **Always use `.table-responsive`** - Wrap all tables in a responsive container
2. **Set minimum widths** - Use `min-w-*` classes on headers to prevent compression
3. **Use semantic colors** - Green for success, red for errors, etc.
4. **Consistent empty states** - Always provide feedback when tables are empty
5. **Proper typography** - Use `font-medium` for primary content, `text-slate-600` for secondary
6. **Accessible links** - Include hover states and proper contrast
7. **Mobile-first** - Test tables on small screens to ensure they remain usable

## Testing Checklist

When creating or updating tables, verify:

- [ ] Table is wrapped in `.table-responsive`
- [ ] Base `.table` class is applied
- [ ] Columns have appropriate width constraints
- [ ] Hover state works correctly (if using `.table-hover`)
- [ ] Empty state displays properly
- [ ] Links and buttons are accessible
- [ ] Table looks good in both light and dark modes
- [ ] Table is usable on mobile devices
- [ ] Text is readable and properly aligned

## Support

For questions or issues with table styling, refer to:
- Tailwind CSS documentation: https://tailwindcss.com/docs
- This repository's `resources/css/app.css` for table class definitions
- Example implementation: `resources/views/livewire/communication/meeting-scheduler.blade.php`
