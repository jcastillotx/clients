# Alpine.js Conversion Requirements

## Files Requiring Alpine.js for Interactive Components

### Profile Management (`resources/views/profile/edit.blade.php`)

**Bootstrap → Alpine.js Conversions Needed:**

1. **Profile Photo Modal** (line 336-357)
   - Currently: `data-toggle="modal" data-target="#profilePhotoModal"`
   - Convert to: Alpine.js `x-data` with modal state

2. **Delete Account Modal** (line 361-391)
   - Currently: `data-toggle="modal" data-target="#deleteAccountModal"`
   - Convert to: Alpine.js modal component

3. **Custom File Input** (line 36-39, JavaScript at line 395-405)
   - Currently: Bootstrap `custom-file-input` with JavaScript
   - Convert to: Alpine.js file input component with reactive label

### Branding Settings (`resources/views/livewire/admin/settings/branding.blade.php`)

**Bootstrap → Alpine.js Conversions Needed:**

1. **Image Preview Modals** (line 830-850)
   - Currently: Bootstrap modal with `data-toggle="modal"`
   - Convert to: Alpine.js modal

2. **Custom File Inputs** (multiple instances)
   - Lines 79-95, 142-156, 201-215, etc.
   - Currently: Bootstrap `custom-file-input` + `input-group-append`
   - Convert to: Tailwind file input with Alpine.js for file name display

3. **Collapsible Sections** (line 264, 321)
   - Currently: `data-toggle="collapse" data-target="#rawTemplateCollapse"`
   - Convert to: Alpine.js `x-show` with toggle

4. **Toast Notifications** (line 902-937, JavaScript)
   - Currently: Custom JavaScript toast system
   - Convert to: Alpine.js toast component

### Email Settings (`resources/views/livewire/admin/settings/email.blade.php`)

**Bootstrap → Alpine.js Conversions Needed:**

1. **Email Builder Modal** (line 383-403)
   - Currently: Bootstrap modal for Unlayer editor
   - Convert to: Alpine.js modal

2. **Collapsible Raw Template** (line 264)
   - Currently: `data-toggle="collapse" data-target="#rawTemplateCollapse"`
   - Convert to: Alpine.js `x-show`

3. **Card Collapse Widget** (line 321-324)
   - Currently: AdminLTE `data-card-widget="collapse"`
   - Convert to: Alpine.js collapsible

### User Permissions (`resources/views/livewire/admin/users/permissions.blade.php`)

**Already Minimal JavaScript:**
- No major interactive components requiring Alpine.js
- Simple Livewire-based checkboxes

## Recommended Alpine.js Components

### 1. Modal Component Pattern
```blade
<div x-data="{ open: false }">
    <!-- Trigger -->
    <button @click="open = true">Open Modal</button>

    <!-- Modal -->
    <div x-show="open"
         x-cloak
         @click.away="open = false"
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Modal content -->
    </div>
</div>
```

### 2. File Input Component Pattern
```blade
<div x-data="{ fileName: 'Choose file...' }">
    <input type="file"
           @change="fileName = $event.target.files[0]?.name || 'Choose file...'"
           class="hidden"
           x-ref="fileInput">
    <button @click="$refs.fileInput.click()" type="button">
        <span x-text="fileName"></span>
    </button>
</div>
```

### 3. Collapsible Section Pattern
```blade
<div x-data="{ expanded: false }">
    <button @click="expanded = !expanded">Toggle</button>
    <div x-show="expanded" x-collapse>
        <!-- Content -->
    </div>
</div>
```

### 4. Toast Notification Pattern
```blade
<div x-data="toast()" @toast.window="show($event.detail)">
    <div x-show="visible" x-transition>
        <span x-text="message"></span>
    </div>
</div>

<script>
function toast() {
    return {
        visible: false,
        message: '',
        show(data) {
            this.message = data.message;
            this.visible = true;
            setTimeout(() => this.visible = false, 5000);
        }
    }
}
</script>
```

## Implementation Priority

1. **High Priority** (User-facing, frequently used):
   - Profile edit modals
   - File upload components

2. **Medium Priority** (Admin features):
   - Branding settings modals
   - Email template builder modal

3. **Low Priority** (Nice-to-have):
   - Collapsible sections
   - Toast notifications

## Notes

- All Alpine.js code should be inline in Blade templates
- Use `x-cloak` to prevent FOUC (Flash of Unstyled Content)
- Leverage `@click.away` for modal backdrop clicks
- Use `@keydown.escape.window` for ESC key to close modals
- Consider creating reusable Blade components for common patterns
