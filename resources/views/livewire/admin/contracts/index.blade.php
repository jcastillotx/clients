<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Contract Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.contracts.generator') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 7H7v6h6V7z" />
                    <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd" />
                </svg>
                AI Generator
            </a>
            <a href="{{ route('admin.contracts.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                New Contract
            </a>
        </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @php
            $statusConfig = [
                'draft' => ['label' => 'Draft', 'color' => 'amber', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                'pending_signature' => ['label' => 'Pending', 'color' => 'blue', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
                'active' => ['label' => 'Active', 'color' => 'emerald', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                'expired' => ['label' => 'Expired', 'color' => 'slate', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                'terminated' => ['label' => 'Terminated', 'color' => 'rose', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
            ];
        @endphp
        @foreach($statusConfig as $key => $cfg)
            <div wire:click="$set('status', '{{ $status === $key ? '' : $key }}')" 
                 class="rounded-xl border p-4 cursor-pointer transition-all {{ $status === $key ? 'border-'.$cfg['color'].'-500 bg-'.$cfg['color'].'-50 ring-2 ring-'.$cfg['color'].'-200' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-{{ $cfg['color'] }}-600 uppercase tracking-wide">{{ $cfg['label'] }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-{{ $cfg['color'] }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cfg['icon'] }}" />
                    </svg>
                </div>
                <div class="text-2xl font-bold text-slate-900">{{ $statusCounts[$key] ?? 0 }}</div>
            </div>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm mb-6">
        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search contracts..." 
                           class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                <select wire:model.live="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client</label>
                <select wire:model.live="clientId" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th wire:click="sortBy('contract_number')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 cursor-pointer hover:bg-slate-100">
                            <div class="flex items-center gap-1">
                                Contract #
                                @if($sortField === 'contract_number')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('title')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 cursor-pointer hover:bg-slate-100">
                            <div class="flex items-center gap-1">
                                Title
                                @if($sortField === 'title')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Client</th>
                        <th wire:click="sortBy('value')" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600 cursor-pointer hover:bg-slate-100">
                            <div class="flex items-center justify-end gap-1">
                                Value
                                @if($sortField === 'value')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('start_date')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 cursor-pointer hover:bg-slate-100">
                            <div class="flex items-center gap-1">
                                Start Date
                                @if($sortField === 'start_date')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('end_date')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 cursor-pointer hover:bg-slate-100">
                            <div class="flex items-center gap-1">
                                End Date
                                @if($sortField === 'end_date')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contracts as $contract)
                        @php
                            $badge = match ($contract->status) {
                                'active' => 'bg-emerald-100 text-emerald-800',
                                'expired' => 'bg-slate-100 text-slate-700',
                                'draft' => 'bg-amber-100 text-amber-800',
                                'pending_signature' => 'bg-blue-100 text-blue-800',
                                'terminated' => 'bg-rose-100 text-rose-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600">
                                {{ $contract->contract_number }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.contracts.edit', $contract) }}" class="text-sm font-semibold text-slate-900 hover:underline">
                                    {{ $contract->title }}
                                </a>
                                @if($contract->description)
                                    <p class="text-xs text-slate-500 truncate max-w-xs">{{ Str::limit($contract->description, 50) }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                {{ $contract->client?->company_name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                @money($contract->value)
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                {{ $contract->start_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                {{ $contract->end_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                                    {{ $contract->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.contracts.edit', $contract) }}" 
                                       class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                        Edit
                                    </a>
                                    <a href="{{ route('contracts.show', $contract) }}" 
                                       class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                        View
                                    </a>
                                    <button wire:click="deleteContract({{ $contract->id }})" 
                                            wire:confirm="Are you sure you want to delete this contract?"
                                            class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-4">No contracts found.</p>
                                <a href="{{ route('admin.contracts.create') }}" class="mt-2 inline-flex items-center gap-1 text-slate-900 font-semibold hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Create your first contract
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $contracts->links() }}
        </div>
    </div>
</div>
