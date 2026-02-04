# Complete Bootstrap to Tailwind Conversion Guide

## Quick Reference Table

| Bootstrap Class | Tailwind Replacement | Notes |
|----------------|---------------------|-------|
| `form-control` | `w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100` | Standard input styling |
| `form-select` | Same as form-control | Dropdown/select styling |
| `form-label` | `block text-sm font-medium text-slate-700 mb-1.5` | Label styling |
| `form-check-input` | `w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500` | Checkbox/radio |
| `form-check-label` | `ml-2 text-sm text-slate-700` | Checkbox/radio label |
| `btn btn-primary` | `btn-brand-primary` | Use custom brand class |
| `btn btn-secondary` | `bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700` | Secondary button |
| `btn btn-outline-primary` | `inline-flex items-center px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100` | Outline button |
| `btn-sm` | Use smaller padding: `px-3 py-1.5 text-xs` | Small button |
| `card` | `bg-white rounded-lg shadow-sm border border-slate-200` | Card container |
| `card-header` | `px-6 py-4 border-b border-slate-200 bg-slate-50` | Card header |
| `card-body` | `p-6` | Card body |
| `card-footer` | `px-6 py-4 border-t border-slate-200 bg-slate-50` | Card footer |
| `card-title` | `font-semibold text-slate-900` | Card title |
| `table` | `w-full border-collapse` | Base table |
| `table-responsive` | `overflow-x-auto` | Scrollable table wrapper |
| `badge bg-primary` | `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800` | Badge |
| `badge-brand-warning` | `badge-brand-warning` | Use custom brand class |
| `alert alert-*` | `alert alert-*` | Keep as-is (defined in app.css) |
| `row` | `grid grid-cols-1 gap-4` | Add responsive cols as needed |
| `col-*` | Use grid column spans | e.g., `md:grid-cols-2 lg:grid-cols-4` |
| `d-flex` | `flex` | Flexbox |
| `d-none` | `hidden` | Hide element |
| `d-block` | `block` | Block element |
| `align-items-center` | `items-center` | Vertical align |
| `justify-content-between` | `justify-between` | Horizontal justify |
| `justify-content-center` | `justify-center` | Center justify |
| `gap-2` | `gap-2` | Same in Tailwind |
| `mb-3` | `mb-3` | Same in Tailwind |
| `text-muted` | `text-slate-500` | Muted text |
| `fw-semibold` | `font-semibold` | Semibold text |
| `fw-bold` | `font-bold` | Bold text |
| `text-end` | `text-right` | Right align text |
| `me-2` | `mr-2` | Margin right |
| `ms-2` | `ml-2` | Margin left |

## Component Patterns

### Standard Form Input

```blade
<!-- Bootstrap -->
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" placeholder="Enter email">
</div>

<!-- Tailwind -->
<div class="mb-3">
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
    <input type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Enter email">
</div>
```

### Select Dropdown

```blade
<!-- Bootstrap -->
<select class="form-select">
    <option>Choose...</option>
    <option>Option 1</option>
</select>

<!-- Tailwind -->
<select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
    <option>Choose...</option>
    <option>Option 1</option>
</select>
```

### Checkbox

```blade
<!-- Bootstrap -->
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="check1">
    <label class="form-check-label" for="check1">Remember me</label>
</div>

<!-- Tailwind -->
<label class="flex items-center">
    <input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" id="check1">
    <span class="ml-2 text-sm text-slate-700">Remember me</span>
</label>
```

### Button Group

```blade
<!-- Bootstrap -->
<div class="btn-group">
    <button class="btn btn-primary">Save</button>
    <button class="btn btn-secondary">Cancel</button>
</div>

<!-- Tailwind -->
<div class="flex gap-2">
    <button class="btn-brand-primary">Save</button>
    <button class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700">Cancel</button>
</div>
```

### Card with Header and Body

```blade
<!-- Bootstrap -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
    </div>
    <div class="card-body">
        <p>Content goes here</p>
    </div>
</div>

<!-- Tailwind -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
        <h3 class="font-semibold text-slate-900">Title</h3>
    </div>
    <div class="p-6">
        <p>Content goes here</p>
    </div>
</div>
```

### Data Table

```blade
<!-- Bootstrap -->
<div class="table-responsive">
    <table class="table table-vcenter table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-semibold">John Doe</td>
                <td class="text-muted">john@example.com</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary">Edit</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Tailwind -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Name</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 text-sm font-semibold">John Doe</td>
                <td class="px-4 py-3 text-sm text-slate-500">john@example.com</td>
                <td class="px-4 py-3 text-right">
                    <button class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">Edit</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Badge/Status Indicator

```blade
<!-- Bootstrap -->
<span class="badge bg-success">Active</span>
<span class="badge bg-warning">Pending</span>
<span class="badge bg-danger">Cancelled</span>

<!-- Tailwind -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Cancelled</span>
```

### Modal/Dialog

```blade
<!-- Bootstrap -->
<div class="modal fade show" style="display:block;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Content
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Tailwind -->
<div class="fixed inset-0 z-50 overflow-y-auto" style="display:block;">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h5 class="text-lg font-semibold text-slate-900">Title</h5>
                <button type="button" class="text-slate-400 hover:text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                Content
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-200 bg-slate-50">
                <button class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700">Cancel</button>
                <button class="btn-brand-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="fixed inset-0 bg-slate-900 bg-opacity-50 transition-opacity z-40"></div>
```

### Grid Layout Examples

```blade
<!-- 2-column responsive grid -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>Column 1</div>
    <div>Column 2</div>
</div>

<!-- 4-column responsive grid -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div>Column 1</div>
    <div>Column 2</div>
    <div>Column 3</div>
    <div>Column 4</div>
</div>

<!-- Mixed column spans -->
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-8">Main content (8 cols on md+)</div>
    <div class="col-span-12 md:col-span-4">Sidebar (4 cols on md+)</div>
</div>
```

## Color Palette Reference

### Status Colors
- **Success**: `bg-green-100 text-green-800` (badges), `bg-green-500` (buttons)
- **Warning**: `bg-amber-100 text-amber-800` (badges), `bg-amber-500` (buttons)
- **Danger**: `bg-red-100 text-red-800` (badges), `bg-red-500` (buttons)
- **Info**: `bg-blue-100 text-blue-800` (badges), `bg-blue-500` (buttons)

### Grayscale
- **Light gray**: `bg-slate-50`, `text-slate-500`
- **Medium gray**: `bg-slate-100`, `text-slate-600`
- **Dark gray**: `bg-slate-700`, `text-slate-900`

### Brand Colors (Use Custom Classes)
- **Primary**: `btn-brand-primary`, `bg-brand-primary`, `text-brand-primary`
- **Secondary**: `btn-brand-secondary`, `bg-brand-secondary`
- **Accent**: `bg-brand-accent`, `text-brand-accent`

## Responsive Breakpoints

| Tailwind | Min Width | Usage |
|----------|-----------|-------|
| `sm:` | 640px | Small tablets |
| `md:` | 768px | Tablets |
| `lg:` | 1024px | Small desktops |
| `xl:` | 1280px | Desktops |
| `2xl:` | 1536px | Large desktops |

### Example Usage
```blade
<!-- Mobile: stack, Tablet: 2 cols, Desktop: 4 cols -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
```

## Common Patterns

### Page Header
```blade
<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
    <div>
        <div class="page-pretitle">Section</div>
        <h2 class="page-title mb-0">Page Title</h2>
    </div>
    <div class="flex gap-2">
        <a href="#" class="btn-brand-primary">Primary Action</a>
        <a href="#" class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700">Secondary</a>
    </div>
</div>
```

### Stats Grid
```blade
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="text-sm font-medium text-slate-500 mb-2">Label</div>
        <div class="text-3xl font-bold text-slate-900">1,234</div>
    </div>
    <!-- Repeat for other stats -->
</div>
```

### Filter Form
```blade
<div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-3">
    <div class="p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Search</label>
                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Search...">
            </div>
            <!-- More filters -->
        </div>
    </div>
</div>
```

## Tips & Best Practices

1. **Always use custom brand classes** for primary buttons: `btn-brand-primary`
2. **Keep alert classes unchanged**: Use existing `alert alert-*` classes from app.css
3. **Use semantic color names**: `slate` for grays, `blue` for primary, `green` for success
4. **Add hover states**: `hover:bg-slate-50` for better UX
5. **Include focus states**: `focus:border-blue-500 focus:ring-4 focus:ring-blue-100`
6. **Use transition classes**: `transition-colors` for smooth state changes
7. **Maintain spacing consistency**: Use standard spacing scale (2, 3, 4, 6, 8, 12)
8. **Test responsiveness**: Always check mobile, tablet, and desktop layouts

## Validation Checklist

Before marking a file as complete, verify:

- [ ] No Bootstrap classes remain (except `alert alert-*`)
- [ ] All forms are functional and styled correctly
- [ ] All buttons use brand classes or Tailwind equivalents
- [ ] Tables have hover states and proper spacing
- [ ] Cards use consistent border/shadow/radius
- [ ] Grid layouts are responsive on all breakpoints
- [ ] Focus states are visible for accessibility
- [ ] Color contrast meets WCAG AA standards
- [ ] Modal/dialogs have backdrop and close buttons
- [ ] File has been tested in browser

## Common Mistakes to Avoid

1. ❌ Using `col-*` classes → ✅ Use CSS Grid with `grid-cols-*`
2. ❌ Using `me-*` / `ms-*` → ✅ Use `mr-*` / `ml-*`
3. ❌ Forgetting focus states on inputs
4. ❌ Not using brand classes for primary buttons
5. ❌ Removing `alert alert-*` classes (keep those!)
6. ❌ Using too many custom colors (stick to palette)
7. ❌ Inconsistent spacing (use standard scale)
8. ❌ Not testing on mobile/tablet sizes

## Questions?

Refer to:
- Tailwind CSS Documentation: https://tailwindcss.com/docs
- Brand classes: `/public/css/brand-tailwind.css`
- App styles: `/resources/css/app.css`
- Conversion progress: `/docs/bootstrap-to-tailwind-conversion-progress.md`
