<div class="space-y-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Social Media API Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure API credentials for social media publishing and management.</p>
    </div>

    <form wire:submit.prevent="saveSocialSettings" class="space-y-4">
        <!-- Facebook -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M20 10c0-5.523-4.477-10-10-10S0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.988C16.343 19.128 20 14.991 20 10z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Facebook / Meta</h4>
                    <p class="text-xs text-slate-500">Required for Facebook Page publishing</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App ID</label>
                    <input type="text" wire:model="social.facebook_client_id" placeholder="Facebook App ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App Secret</label>
                    <input type="password" wire:model="social.facebook_client_secret" placeholder="Facebook App Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Instagram -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 0C7.284 0 6.944.012 5.877.06 4.813.109 4.087.278 3.45.525a4.89 4.89 0 00-1.772 1.153A4.89 4.89 0 00.525 3.45C.278 4.087.109 4.812.06 5.877.012 6.944 0 7.284 0 10s.012 3.056.06 4.123c.049 1.064.218 1.79.465 2.427a4.89 4.89 0 001.153 1.772 4.89 4.89 0 001.772 1.153c.637.247 1.363.416 2.427.465C6.944 19.988 7.284 20 10 20s3.056-.012 4.123-.06c1.064-.049 1.79-.218 2.427-.465a4.89 4.89 0 001.772-1.153 4.89 4.89 0 001.153-1.772c.247-.637.416-1.363.465-2.427.048-1.067.06-1.407.06-4.123s-.012-3.056-.06-4.123c-.049-1.064-.218-1.79-.465-2.427a4.89 4.89 0 00-1.153-1.772A4.89 4.89 0 0016.55.525C15.913.278 15.187.109 14.123.06 13.056.012 12.716 0 10 0zm0 4.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 8.468a3.333 3.333 0 100-6.666 3.333 3.333 0 000 6.666zm5.338-9.87a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Instagram (via Facebook)</h4>
                    <p class="text-xs text-slate-500">Uses Facebook Graph API for Instagram Business accounts</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App ID</label>
                    <input type="text" wire:model="social.instagram_client_id" placeholder="Instagram/Facebook App ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App Secret</label>
                    <input type="password" wire:model="social.instagram_client_secret" placeholder="Instagram/Facebook App Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- LinkedIn -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.338 16.338H13.67V12.16c0-.995-.017-2.277-1.387-2.277-1.39 0-1.601 1.086-1.601 2.207v4.248H8.014v-8.59h2.559v1.174h.037c.356-.675 1.227-1.387 2.526-1.387 2.703 0 3.203 1.778 3.203 4.092v4.711zM5.005 6.575a1.548 1.548 0 11-.003-3.096 1.548 1.548 0 01.003 3.096zm-1.337 9.763H6.34v-8.59H3.667v8.59zM17.668 1H2.328C1.595 1 1 1.581 1 2.298v15.403C1 18.418 1.595 19 2.328 19h15.34c.734 0 1.332-.582 1.332-1.299V2.298C19 1.581 18.402 1 17.668 1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">LinkedIn</h4>
                    <p class="text-xs text-slate-500">Required for LinkedIn publishing</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client ID</label>
                    <input type="text" wire:model="social.linkedin_client_id" placeholder="LinkedIn Client ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Secret</label>
                    <input type="password" wire:model="social.linkedin_client_secret" placeholder="LinkedIn Client Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Twitter/X -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-900" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M11.903 8.596L18.469 1h-1.555l-5.7 6.596L6.574 1H1l6.893 10.001L1 19h1.555l6.025-6.969L13.426 19H19l-7.097-10.404zm-2.132 2.467l-.698-.996L3.37 2.21h2.392l4.482 6.387.698.996 5.826 8.303h-2.391l-4.756-6.833z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">X (Twitter)</h4>
                    <p class="text-xs text-slate-500">Requires Twitter API v2 access</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                    <input type="text" wire:model="social.twitter_api_key" placeholder="API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Secret</label>
                    <input type="password" wire:model="social.twitter_api_secret" placeholder="API Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Access Token</label>
                    <input type="text" wire:model="social.twitter_access_token" placeholder="Access Token" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Access Secret</label>
                    <input type="password" wire:model="social.twitter_access_secret" placeholder="Access Token Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- TikTok -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.6 5.82s.51.5 0 0A4.278 4.278 0 0115.54 3h-3.09v12.4a2.592 2.592 0 01-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3s-1.88.09-3.24-1.48z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">TikTok</h4>
                    <p class="text-xs text-slate-500">Required for TikTok Business account publishing</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Key</label>
                    <input type="text" wire:model="social.tiktok_client_key" placeholder="TikTok Client Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Secret</label>
                    <input type="password" wire:model="social.tiktok_client_secret" placeholder="TikTok Client Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Threads -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                    <span class="text-white font-bold text-sm">@</span>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Threads (Meta)</h4>
                    <p class="text-xs text-slate-500">Uses Instagram API for Threads publishing</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client ID</label>
                    <input type="text" wire:model="social.threads_client_id" placeholder="Threads/Instagram App ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Secret</label>
                    <input type="password" wire:model="social.threads_client_secret" placeholder="Threads/Instagram App Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Pinterest -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0C4.477 0 0 4.477 0 10c0 4.237 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.034-2.868.183-.78 1.17-4.958 1.17-4.958s-.299-.598-.299-1.48c0-1.388.806-2.425 1.81-2.425.852 0 1.264.64 1.264 1.408 0 .858-.545 2.14-.828 3.33-.236.995.5 1.807 1.48 1.807 1.778 0 3.144-1.874 3.144-4.58 0-2.393-1.72-4.068-4.177-4.068-2.845 0-4.515 2.135-4.515 4.34 0 .859.331 1.781.745 2.281a.3.3 0 01.069.288l-.278 1.133c-.044.183-.145.223-.335.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.965-.527-2.292-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.523 0 10-4.477 10-10S15.523 0 10 0z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900">Pinterest</h4>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App ID</label>
                    <input type="text" wire:model="social.pinterest_app_id" placeholder="Pinterest App ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App Secret</label>
                    <input type="password" wire:model="social.pinterest_app_secret" placeholder="Pinterest App Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Bluesky -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">Bluesky</h4>
                    <p class="text-xs text-slate-500">Uses AT Protocol - create an App Password</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Handle/Identifier</label>
                    <input type="text" wire:model="social.bluesky_identifier" placeholder="yourhandle.bsky.social" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">App Password</label>
                    <input type="password" wire:model="social.bluesky_password" placeholder="App Password" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <!-- Mastodon -->
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M19.27 6.73c-.23-1.64-1.74-2.92-3.48-3.19C14.13 3.25 10 3 10 3s-4.13.25-5.79.54C2.47 3.81.96 5.09.73 6.73.5 8.37.5 10 .5 10s0 1.63.23 3.27c.23 1.64 1.74 2.92 3.48 3.19 1.66.29 5.79.54 5.79.54s4.13-.25 5.79-.54c1.74-.27 3.25-1.55 3.48-3.19.23-1.64.23-3.27.23-3.27s0-1.63-.23-3.27zM8.5 13V7l5 3-5 3z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-900">Mastodon</h4>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Instance URL</label>
                    <input type="text" wire:model="social.mastodon_instance" placeholder="https://mastodon.social" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Access Token</label>
                    <input type="password" wire:model="social.mastodon_access_token" placeholder="Mastodon Access Token" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveSocialSettings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z" />
                    </svg>
                    Save Social Media Settings
                </span>
                <span wire:loading wire:target="saveSocialSettings" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
