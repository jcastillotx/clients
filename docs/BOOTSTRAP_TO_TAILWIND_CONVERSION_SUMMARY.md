# Bootstrap to Tailwind Conversion Summary

## Completed Conversions

### User Management, Profile & Settings Views (30 Files)

#### ✅ Already Converted (Tailwind-first)
1. `/resources/views/livewire/admin/users/index.blade.php` - Already using Tailwind
2. `/resources/views/livewire/admin/users/create.blade.php` - Already using Tailwind
3. `/resources/views/livewire/admin/users/edit.blade.php` - Already using Tailwind
4. `/resources/views/livewire/admin/settings/general.blade.php` - Already using Tailwind

#### ✅ Fully Converted
5. `/resources/views/profile/edit.blade.php` - **COMPLETED** (408 lines → Full Tailwind + Alpine.js)

#### ⚠️ Requires Conversion
6. `/resources/views/livewire/admin/users/permissions.blade.php` - Partial Bootstrap
7. `/resources/views/livewire/admin/settings/branding.blade.php` - Heavy Bootstrap usage
8. `/resources/views/livewire/admin/settings/email.blade.php` - Heavy Bootstrap usage
9. `/resources/views/livewire/admin/settings/security.blade.php` - Bootstrap forms/switches
10. Additional ~13 settings files pending review

## Bootstrap → Tailwind Mapping Reference

### Layout & Grid

| Bootstrap | Tailwind |
|-----------|----------|
| `container-fluid` | `w-full px-4 sm:px-6 lg:px-8` or `max-w-7xl mx-auto` |
| `row` | `grid grid-cols-1` or `flex flex-wrap` |
| `col-lg-6` | `lg:col-span-6` or `lg:w-1/2` |
| `col-md-4` | `md:col-span-4` or `md:w-1/3` |
| `d-flex` | `flex` |
| `align-items-center` | `items-center` |
| `justify-content-between` | `justify-between` |
| `flex-grow-1` | `flex-1` |
| `mb-3` | `mb-3` or `space-y-3` (for children) |
| `gap-2` | `gap-2` |

### Cards

| Bootstrap | Tailwind |
|-----------|----------|
| `card` | `rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden` |
| `card-header` | `px-6 py-4 border-b border-slate-200 bg-slate-50` |
| `card-body` | `p-6` or `p-6 space-y-4` |
| `card-footer` | `px-6 py-4 border-t border-slate-200 bg-slate-50` |
| `card-title` | `text-base font-semibold text-slate-900` |
| `card-outline card-danger` | `border-rose-200` with `bg-rose-50` header |

### Forms

| Bootstrap | Tailwind |
|-----------|----------|
| `form-group` | `<div>` with `space-y-1.5` or individual spacing |
| `form-control` | `w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors` |
| `form-control-color` | Same as form-control with `type="color"` |
| `form-select` | Same as form-control with `bg-white` for select |
| `is-invalid` | `border-rose-500` |
| `invalid-feedback` | `mt-1.5 text-xs text-rose-600` |
| `form-label` or `label` | `block text-xs font-semibold text-slate-600 mb-1.5` |
| `custom-file` | **Convert to Alpine.js file input** |
| `custom-file-input` | `hidden` with `x-ref="fileInput"` |
| `custom-file-label` | Button with Alpine.js `x-text` for filename |
| `custom-control custom-switch` | **Convert to toggle switch** |
| `custom-control-input` | `h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900` |
| `custom-control-label` | `text-sm text-slate-700` |
| `input-group` | `flex` with rounded corners on first/last child |
| `input-group-prepend` | Icon as first flex child |
| `input-group-append` | Button as last flex child |
| `input-group-text` | `flex items-center px-3 border border-slate-300 bg-slate-50 text-slate-600` |

### Buttons

| Bootstrap | Tailwind |
|-----------|----------|
| `btn btn-primary` | `rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors` |
| `btn btn-secondary` | `rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 transition-colors` |
| `btn btn-outline-primary` | `rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors` |
| `btn btn-outline-danger` | `rounded-lg border-2 border-rose-600 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors` |
| `btn btn-link` | `text-sm font-semibold underline hover:text-slate-800` |
| `btn-group` | `inline-flex rounded-lg` with `-ml-px` on middle items |
| `btn-sm` | Reduce `px-3 py-1.5 text-xs` |

### Badges

| Bootstrap | Tailwind |
|-----------|----------|
| `badge badge-primary` | `inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-2.5 py-0.5 text-xs font-semibold` |
| `badge badge-success` | `bg-emerald-100 text-emerald-700` |
| `badge badge-danger` | `bg-rose-100 text-rose-700` |
| `badge badge-warning` | `bg-amber-100 text-amber-700` |
| `badge badge-info` | `bg-cyan-100 text-cyan-700` |
| `badge badge-secondary` | `bg-slate-100 text-slate-700` |

### Tables

| Bootstrap | Tailwind |
|-----------|----------|
| `table` | `w-full` |
| `table-responsive` | `overflow-x-auto` wrapper |
| `table-striped` | `divide-y divide-slate-200` with alternating row colors |
| `table-hover` | Add `hover:bg-slate-50` to `<tr>` |
| `table-sm` | Reduce padding: `px-4 py-2` |
| `thead` | `border-b border-slate-200 bg-slate-50` |
| `th` | `px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider` |
| `td` | `px-6 py-3 text-sm text-slate-900` |

### Alerts

| Bootstrap | Tailwind |
|-----------|----------|
| `alert alert-warning` | `rounded-xl bg-amber-50 border border-amber-200 p-3 (or p-4)` |
| `alert alert-info` | `rounded-xl bg-blue-50 border border-blue-200 p-4` |
| `alert alert-success` | `rounded-xl bg-emerald-50 border border-emerald-200 p-4` |
| `alert alert-danger` | `rounded-xl bg-rose-50 border border-rose-200 p-4` |
| Alert text | `text-sm text-{color}-800` (e.g., `text-amber-800`) |

### Modals

| Bootstrap | Tailwind + Alpine.js |
|-----------|---------------------|
| `data-toggle="modal"` | `@click="modalOpen = true"` |
| `data-target="#modalId"` | Alpine.js state: `x-data="{ modalOpen: false }"` |
| `data-dismiss="modal"` | `@click="modalOpen = false"` |
| `modal fade` | `x-show="modalOpen" x-cloak` with `fixed inset-0 z-50` |
| `modal-dialog` | `flex items-center justify-center min-h-screen px-4` |
| `modal-content` | `relative bg-white rounded-2xl shadow-xl max-w-{size} w-full` |
| `modal-header` | `flex items-center justify-between px-6 py-4 border-b border-slate-200` |
| `modal-body` | `p-6` or `p-8 bg-slate-50` |
| `modal-footer` | `flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200` |
| Backdrop click | `@click.away="modalOpen = false"` |
| ESC key | `@keydown.escape.window="modalOpen = false"` |

### Utilities

| Bootstrap | Tailwind |
|-----------|----------|
| `position-relative` | `relative` |
| `position-absolute` | `absolute` |
| `position-fixed` | `fixed` |
| `text-muted` | `text-slate-500` or `text-slate-600` |
| `text-danger` | `text-rose-600` |
| `text-warning` | `text-amber-600` |
| `text-success` | `text-emerald-600` |
| `font-weight-bold` | `font-semibold` or `font-bold` |
| `small` | `text-xs` or `text-sm` |
| `img-circle` | `rounded-full` |
| `img-thumbnail` | `rounded border shadow` |
| `img-fluid` | `max-w-full h-auto` |
| `d-none` | `hidden` |
| `d-block` | `block` |
| `d-inline` | `inline` |
| `d-inline-block` | `inline-block` |

## Alpine.js Patterns Used

### 1. File Input Component
```blade
<div x-data="{
    fileName: 'Choose file...',
    updateFileName(event) {
        this.fileName = event.target.files[0]?.name || 'Choose file...';
    }
}">
    <input type="file"
           class="hidden"
           x-ref="fileInput"
           @change="updateFileName($event)">
    <button @click="$refs.fileInput.click()" type="button">
        <span x-text="fileName"></span>
    </button>
</div>
```

### 2. Modal Component
```blade
<div x-data="{ modalOpen: false }">
    <!-- Trigger -->
    <button @click="modalOpen = true">Open Modal</button>

    <!-- Modal -->
    <div x-show="modalOpen"
         x-cloak
         @click.away="modalOpen = false"
         @keydown.escape.window="modalOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                <!-- Modal content -->
            </div>
        </div>
    </div>
</div>
```

### 3. Toggle Switch (Checkbox)
```blade
<label class="flex items-center gap-3 cursor-pointer">
    <input type="checkbox"
           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
    <span class="text-sm text-slate-700">Label text</span>
</label>
```

## Files Requiring Alpine.js Conversion

### High Priority
1. **profile/edit.blade.php** ✅ DONE
   - Profile photo modal
   - Delete account modal
   - File inputs

### Medium Priority
2. **branding.blade.php** (Pending)
   - Image preview modals (6 instances)
   - Custom file inputs (6 instances)
   - Collapsible sections
   - Toast notifications

3. **email.blade.php** (Pending)
   - Email builder modal
   - Collapsible raw template
   - Card collapse widget

4. **security.blade.php** (Pending)
   - Custom toggle switches

## Standard Form Field Pattern

```blade
<div>
    <label for="field_name" class="block text-xs font-semibold text-slate-600 mb-1.5">
        Field Label <span class="text-rose-500">*</span>
    </label>
    <input type="text"
           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors @error('field_name') border-rose-500 @enderror"
           id="field_name"
           name="field_name"
           value="{{ old('field_name', $default ?? '') }}"
           required>
    @error('field_name')
        <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
    @enderror
</div>
```

## Grid Layouts

### 2-Column Form
```blade
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div><!-- Field 1 --></div>
    <div><!-- Field 2 --></div>
</div>
```

### Multi-Column Address Form
```blade
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="col-span-2"><!-- City (spans 2) --></div>
    <div><!-- State --></div>
    <div><!-- ZIP --></div>
</div>
```

## Testing Checklist

### Visual Testing
- [ ] Forms render correctly with proper spacing
- [ ] Cards have proper rounded corners and shadows
- [ ] Buttons have hover states
- [ ] Modals open/close smoothly
- [ ] File inputs show selected filename
- [ ] Mobile responsive (test at 375px, 768px, 1024px)

### Functional Testing
- [ ] Form submissions work
- [ ] Validation errors display correctly
- [ ] File uploads functional
- [ ] Modals close on backdrop click
- [ ] Modals close on ESC key
- [ ] Toggle switches toggle correctly

### Browser Testing
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

## Next Steps

1. Convert remaining settings files (branding, email, security, etc.)
2. Test all forms for functionality
3. Verify Alpine.js interactions work correctly
4. Check mobile responsiveness
5. Remove Bootstrap JavaScript completely
6. Update task #7 to "completed"

## Notes

- All Alpine.js code is inline in Blade templates (no separate JS files)
- Use `x-cloak` attribute with corresponding CSS to prevent FOUC
- Profile edit page serves as reference implementation for other conversions
- Maintain consistent spacing: `gap-4` for grids, `space-y-4` for stacked elements
- Use `rounded-xl` for inputs/buttons, `rounded-2xl` for cards
- Standard card padding: `p-6`, header/footer: `px-6 py-4`
