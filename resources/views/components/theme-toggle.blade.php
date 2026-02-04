@props(['class' => ''])

<div {{ $attributes->merge(['class' => "flex items-center gap-2 $class"]) }} x-data="themeToggle()">
    <!-- Theme Toggle -->
    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
        <button 
            @click="setTheme('light')"
            :class="theme === 'light' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
            class="p-2 rounded-md transition-all duration-200 hover:bg-white/50 dark:hover:bg-slate-700/50"
            title="Light Mode">
            <x-icon name="sun" class="w-4 h-4 text-slate-600 dark:text-slate-400" />
        </button>
        <button 
            @click="setTheme('dark')"
            :class="theme === 'dark' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
            class="p-2 rounded-md transition-all duration-200 hover:bg-white/50 dark:hover:bg-slate-700/50"
            title="Dark Mode">
            <x-icon name="moon" class="w-4 h-4 text-slate-600 dark:text-slate-400" />
        </button>
    </div>

    <!-- Density Toggle -->
    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
        <button 
            @click="setDensity('comfy')"
            :class="density === 'comfy' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
            class="px-2 py-1 rounded-md text-xs font-medium transition-all duration-200 hover:bg-white/50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-400"
            title="Comfy Spacing">
            L
        </button>
        <button 
            @click="setDensity('compact')"
            :class="density === 'compact' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
            class="px-2 py-1 rounded-md text-xs font-medium transition-all duration-200 hover:bg-white/50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-400"
            title="Compact Spacing">
            M
        </button>
        <button 
            @click="setDensity('extreme')"
            :class="density === 'extreme' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
            class="px-2 py-1 rounded-md text-xs font-medium transition-all duration-200 hover:bg-white/50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-400"
            title="Extreme Compact">
            S
        </button>
    </div>
</div>

<script>
function themeToggle() {
    return {
        theme: localStorage.getItem('theme') || 'light',
        density: localStorage.getItem('density') || 'comfy',
        
        init() {
            this.applyTheme();
            this.applyDensity();
        },
        
        setTheme(newTheme) {
            this.theme = newTheme;
            localStorage.setItem('theme', newTheme);
            this.applyTheme();
        },
        
        setDensity(newDensity) {
            this.density = newDensity;
            localStorage.setItem('density', newDensity);
            this.applyDensity();
        },
        
        applyTheme() {
            document.documentElement.setAttribute('data-theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        
        applyDensity() {
            document.documentElement.setAttribute('data-density', this.density);
        }
    }
}
</script>