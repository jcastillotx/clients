<!-- List View -->
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Task</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Assignees</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Progress</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tasks as $task)
                    <tr class="hover:bg-slate-50 {{ $task->is_overdue ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="font-medium text-slate-900">{{ $task->title }}</div>
                                    @if($task->description)
                                        <div class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $task->description }}</div>
                                    @endif
                                    @if($task->labels->count() > 0)
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($task->labels as $label)
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium text-white" style="background-color: {{ $label->color }}">
                                                    {{ $label->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $task->column->color }}20; color: {{ $task->column->color }}">
                                {{ $task->column->name }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full bg-{{ $task->priority_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $task->priority_color }}-800">
                                {{ $task->priority_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($task->assignees->count() > 0)
                                <div class="flex -space-x-2">
                                    @foreach($task->assignees->take(3) as $assignee)
                                        <div class="h-7 w-7 rounded-full bg-slate-300 ring-2 ring-white flex items-center justify-center text-[10px] font-medium text-slate-700" title="{{ $assignee->name }}">
                                            {{ strtoupper(substr($assignee->name, 0, 2)) }}
                                        </div>
                                    @endforeach
                                    @if($task->assignees->count() > 3)
                                        <div class="h-7 w-7 rounded-full bg-slate-200 ring-2 ring-white flex items-center justify-center text-[10px] font-medium text-slate-600">
                                            +{{ $task->assignees->count() - 3 }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                            {{ $task->client?->company_name ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            @if($task->due_date)
                                <span class="{{ $task->is_overdue ? 'font-semibold text-red-600' : ($task->is_due_soon ? 'text-amber-600' : 'text-slate-600') }}">
                                    {{ $task->due_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($task->checklists->count() > 0)
                                @php $progress = $task->checklist_progress; @endphp
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full {{ $progress['percentage'] === 100 ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ $progress['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ $progress['percentage'] }}%</span>
                                </div>
                            @elseif($task->progress > 0)
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-blue-500" style="width: {{ $task->progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ $task->progress }}%</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button wire:click="openTaskModal({{ $task->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                            No tasks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
