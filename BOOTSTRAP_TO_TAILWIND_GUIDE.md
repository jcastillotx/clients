# Bootstrap to Tailwind CSS - Quick Reference Guide

> **Quick lookup table** for common Bootstrap → Tailwind conversions used in this project

---

## 🎯 Layout & Flexbox

| Bootstrap | Tailwind CSS | Notes |
|-----------|--------------|-------|
| `d-flex` | `flex` | Basic flexbox |
| `d-inline-flex` | `inline-flex` | Inline flexbox |
| `flex-row` | `flex-row` | Row direction (default) |
| `flex-column` | `flex-col` | Column direction |
| `flex-wrap` | `flex-wrap` | Wrap items |
| `flex-nowrap` | `flex-nowrap` | No wrapping |
| `align-items-start` | `items-start` | Align start |
| `align-items-center` | `items-center` | Align center |
| `align-items-end` | `items-end` | Align end |
| `justify-content-start` | `justify-start` | Justify start |
| `justify-content-center` | `justify-center` | Justify center |
| `justify-content-end` | `justify-end` | Justify end |
| `justify-content-between` | `justify-between` | Space between |
| `justify-content-around` | `justify-around` | Space around |
| `gap-1` to `gap-5` | `gap-1` to `gap-5` | Already Tailwind! |

---

## 📐 Grid System

| Bootstrap | Tailwind CSS | Notes |
|-----------|--------------|-------|
| `container` | `container mx-auto px-4` | Container with padding |
| `row` | `flex flex-wrap -mx-4` | Negative margin for gutters |
| `col` | `flex-1 px-4` | Auto column |
| `col-12` | `w-full px-4` | Full width |
| `col-6` | `w-1/2 px-4` | Half width |
| `col-4` | `w-1/3 px-4` | One third |
| `col-3` | `w-1/4 px-4` | One quarter |
| `col-md-6` | `md:w-1/2 px-4` | Half width on medium+ |
| `col-lg-4` | `lg:w-1/3 px-4` | One third on large+ |
| `g-3` | `gap-3` | Grid gap |
| `gx-2` | `gap-x-2` | Horizontal gap only |
| `gy-2` | `gap-y-2` | Vertical gap only |

**Modern Grid Alternative:**
```html
<!-- Bootstrap -->
<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">...</div>
</div>

<!-- Tailwind Grid (Recommended) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    <div>...</div>
</div>
```

---

## 🎨 Colors & Typography

| Bootstrap | Tailwind CSS | Notes |
|-----------|--------------|-------|
| `text-primary` | `text-blue-600` | Primary color |
| `text-secondary` | `text-gray-600` | Secondary color |
| `text-success` | `text-green-600` | Success color |
| `text-danger` | `text-red-600` | Danger/error color |
| `text-warning` | `text-yellow-600` | Warning color |
| `text-info` | `text-cyan-600` | Info color |
| `text-muted` | `text-gray-500` | Muted text |
| `text-dark` | `text-gray-900` | Dark text |
| `text-white` | `text-white` | White text |
| `bg-primary` | `bg-blue-600` | Primary background |
| `bg-light` | `bg-gray-100` | Light background |
| `bg-dark` | `bg-gray-900` | Dark background |
| `bg-white` | `bg-white` | White background |

### Typography Sizing
| Bootstrap | Tailwind CSS |
|-----------|--------------|
| `h1` | `text-3xl font-bold` or `text-4xl font-bold` |
| `h2` | `text-2xl font-bold` or `text-3xl font-bold` |
| `h3` | `text-xl font-bold` or `text-2xl font-bold` |
| `h4` | `text-lg font-bold` or `text-xl font-bold` |
| `h5` | `text-base font-bold` |
| `h6` | `text-sm font-bold` |
| `small` | `text-sm` |
| `lead` | `text-lg` |

---

## 🔘 Buttons

### Basic Buttons
```html
<!-- Bootstrap -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-outline-primary">Outline</button>
<button class="btn btn-danger">Delete</button>

<!-- Tailwind -->
<button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
    Primary
</button>
<button class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium">
    Secondary
</button>
<button class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 font-medium">
    Outline
</button>
<button class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium">
    Delete
</button>
```

### Button Sizes
| Bootstrap | Tailwind CSS |
|-----------|--------------|
| `btn-sm` | `px-3 py-1.5 text-sm` |
| `btn` (default) | `px-4 py-2 text-base` |
| `btn-lg` | `px-6 py-3 text-lg` |

---

## 📦 Cards

```html
<!-- Bootstrap -->
<div class="card">
    <div class="card-header">Header</div>
    <div class="card-body">Body</div>
    <div class="card-footer">Footer</div>
</div>

<!-- Tailwind -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">Header</div>
    <div class="p-6">Body</div>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">Footer</div>
</div>
```

---

## 📝 Forms

### Text Input
```html
<!-- Bootstrap -->
<label class="form-label">Label</label>
<input type="text" class="form-control" placeholder="Enter text">

<!-- Tailwind -->
<label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
<input type="text" 
       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
       placeholder="Enter text">
```

### Select
```html
<!-- Bootstrap -->
<label class="form-label">Choose</label>
<select class="form-select">
    <option>Option 1</option>
</select>

<!-- Tailwind -->
<label class="block text-sm font-medium text-gray-700 mb-1">Choose</label>
<select class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
    <option>Option 1</option>
</select>
```

### Textarea
```html
<!-- Bootstrap -->
<textarea class="form-control" rows="3"></textarea>

<!-- Tailwind -->
<textarea rows="3" 
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
</textarea>
```

### Checkbox
```html
<!-- Bootstrap -->
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="check1">
    <label class="form-check-label" for="check1">Check me</label>
</div>

<!-- Tailwind -->
<div class="flex items-center">
    <input type="checkbox" id="check1" 
           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
    <label for="check1" class="ml-2 text-sm text-gray-700">Check me</label>
</div>
```

### Radio Button
```html
<!-- Bootstrap -->
<div class="form-check">
    <input class="form-check-input" type="radio" name="radio" id="radio1">
    <label class="form-check-label" for="radio1">Option 1</label>
</div>

<!-- Tailwind -->
<div class="flex items-center">
    <input type="radio" name="radio" id="radio1" 
           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
    <label for="radio1" class="ml-2 text-sm text-gray-700">Option 1</label>
</div>
```

---

## 📑 Tabs & Navigation

### Tabs
```html
<!-- Bootstrap -->
<ul class="nav nav-tabs">
    <li class="nav-item">
        <button class="nav-link active">Tab 1</button>
    </li>
    <li class="nav-item">
        <button class="nav-link">Tab 2</button>
    </li>
</ul>

<!-- Tailwind -->
<nav class="border-b border-gray-200">
    <div class="-mb-px flex space-x-8">
        <button class="border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
            Tab 1
        </button>
        <button class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Tab 2
        </button>
    </div>
</nav>
```

### Pills
```html
<!-- Bootstrap -->
<ul class="nav nav-pills">
    <li class="nav-item">
        <button class="nav-link active">Pill 1</button>
    </li>
</ul>

<!-- Tailwind -->
<nav class="flex space-x-2">
    <button class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium">
        Pill 1
    </button>
    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200">
        Pill 2
    </button>
</nav>
```

---

## 📊 Tables

```html
<!-- Bootstrap -->
<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>John Doe</td>
            <td>john@example.com</td>
        </tr>
    </tbody>
</table>

<!-- Tailwind -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Email
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    John Doe
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    john@example.com
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## 🔲 Spacing

### Margin
| Bootstrap | Tailwind CSS |
|-----------|--------------|
| `m-0` | `m-0` |
| `m-1` | `m-1` (0.25rem) |
| `m-2` | `m-2` (0.5rem) |
| `m-3` | `m-3` (0.75rem) |
| `m-4` | `m-4` (1rem) |
| `m-5` | `m-5` (1.25rem) |
| `mt-3` | `mt-3` |
| `mb-3` | `mb-3` |
| `mx-3` | `mx-3` |
| `my-3` | `my-3` |

### Padding
| Bootstrap | Tailwind CSS |
|-----------|--------------|
| `p-0` | `p-0` |
| `p-1` | `p-1` |
| `p-2` | `p-2` |
| `p-3` | `p-3` |
| `p-4` | `p-4` |
| `p-5` | `p-5` |
| `pt-3` | `pt-3` |
| `pb-3` | `pb-3` |
| `px-3` | `px-3` |
| `py-3` | `py-3` |

---

## 🎭 Display

| Bootstrap | Tailwind CSS |
|-----------|--------------|
| `d-none` | `hidden` |
| `d-block` | `block` |
| `d-inline` | `inline` |
| `d-inline-block` | `inline-block` |
| `d-flex` | `flex` |
| `d-inline-flex` | `inline-flex` |
| `d-grid` | `grid` |

---

## 📱 Responsive Breakpoints

| Bootstrap | Tailwind CSS | Screen Size |
|-----------|--------------|-------------|
| (default) | (default) | < 640px |
| `sm-` | `sm:` | ≥ 640px |
| `md-` | `md:` | ≥ 768px |
| `lg-` | `lg:` | ≥ 1024px |
| `xl-` | `xl:` | ≥ 1280px |
| `xxl-` | `2xl:` | ≥ 1536px |

**Example:**
```html
<!-- Bootstrap -->
<div class="col-12 col-md-6 col-lg-4">Content</div>

<!-- Tailwind -->
<div class="w-full md:w-1/2 lg:w-1/3">Content</div>
```

---

## ⚡ Common Patterns

### Page Header
```html
<!-- Bootstrap -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-pretitle">Section</div>
        <h2 class="page-title mb-0">Page Title</h2>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Action</button>
    </div>
</div>

<!-- Tailwind -->
<div class="flex items-center justify-between mb-3">
    <div>
        <div class="text-sm text-gray-500 font-medium">Section</div>
        <h2 class="text-2xl font-bold">Page Title</h2>
    </div>
    <div class="flex gap-2">
        <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
            Action
        </button>
    </div>
</div>
```

### Stats Cards
```html
<!-- Bootstrap -->
<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Stat Label</div>
                <div class="h1 mb-0">$12,345</div>
            </div>
        </div>
    </div>
</div>

<!-- Tailwind -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-sm text-gray-500 font-medium mb-2">Stat Label</div>
        <div class="text-3xl font-bold">$12,345</div>
    </div>
</div>
```

### Filter Bar
```html
<!-- Bootstrap -->
<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" placeholder="Search...">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Status</label>
                <select class="form-select">
                    <option>All</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Tailwind -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <div class="lg:col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                   placeholder="Search...">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:ring-blue-500 focus:border-blue-500">
                <option>All</option>
            </select>
        </div>
    </div>
</div>
```

---

## 💡 Pro Tips

1. **Use Tailwind's Grid over Flexbox for complex layouts**  
   Grid is more powerful for 2D layouts

2. **Combine utilities for hover states**  
   `hover:bg-blue-700` `hover:border-gray-300`

3. **Use arbitrary values when needed**  
   `w-[375px]` `text-[15px]` for one-off cases

4. **Focus states are important**  
   Always include `focus:ring-*` and `focus:border-*`

5. **Responsive design mobile-first**  
   Start with mobile classes, add `md:` and `lg:` for larger screens

6. **Use @apply for repeated patterns** (in CSS file)
   ```css
   .btn-primary {
       @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium;
   }
   ```

---

## 🔗 Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Tailwind UI Components](https://tailwindui.com/components)
- [PAGES_INVENTORY.md](./PAGES_INVENTORY.md) - Complete page catalog
- [STYLING_CONVERSION_REPORT.md](./STYLING_CONVERSION_REPORT.md) - Detailed conversion analysis

---

**Last Updated**: 2026-02-05  
**Version**: 1.0
