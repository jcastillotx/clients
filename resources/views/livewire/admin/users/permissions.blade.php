<div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Permission Matrix</h1>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
            Back
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Create custom role</label>
                    <input type="text"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           placeholder="e.g. project_manager"
                           wire:model.live.debounce.350ms="newRoleName">
                </div>
                <div class="md:col-span-3">
                    <button type="button"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
                            wire:click="createRole">
                        Create role
                    </button>
                </div>
                <div class="md:col-span-3 text-xs text-slate-500">
                    Roles and permissions are managed via Spatie.
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="min-width: 260px;">Permission</th>
                    @foreach($roles as $r)
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider" style="min-width: 140px;">
                            {{ str_replace('_', ' ', ucfirst($r)) }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @foreach($this->permissionGroups as $group => $perms)
                    @if(empty($perms)) @continue @endif
                    <tr>
                        <td colspan="{{ 1 + count($roles) }}" class="px-4 py-2 bg-slate-50 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            {{ $group }}
                        </td>
                    </tr>
                    @foreach($perms as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600">{{ $p }}</td>
                            @foreach($roles as $r)
                                @php $has = in_array($p, $this->rolePermissions[$r] ?? [], true); @endphp
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0 cursor-pointer"
                                           @checked($has)
                                           wire:click="toggle('{{ $r }}', '{{ $p }}')">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 text-xs text-slate-600">
            Tip: keep <strong>staff</strong> permissions minimal and use client assignments to scope access.
        </div>
    </div>
</div>

