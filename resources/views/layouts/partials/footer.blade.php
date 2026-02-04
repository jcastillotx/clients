<footer class="bg-white border-t border-slate-200 py-4 px-6 mt-8">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-sm text-slate-600">
        <div>
            &copy; {{ date('Y') }} <a href="{{ config('branding.company.website', 'https://kre8ivdesigns.com') }}" class="text-blue-600 hover:text-blue-700">{{ config('branding.company.name', 'Kre8iv Designs') }}</a>. All rights reserved.
        </div>
        <div class="hidden sm:block">
            <span class="font-semibold">Version</span> {{ config('app.version', '1.0.0') }}
        </div>
    </div>
</footer>
