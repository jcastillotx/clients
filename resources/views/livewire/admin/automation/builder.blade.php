<x-app-layout>
    <x-slot name="header">{{ $ruleId ? 'Edit Automation' : 'New Automation' }}</x-slot>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.automation.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
        <a href="{{ route('admin.automation.logs', ['ruleId' => $ruleId]) }}" class="btn btn-outline-info">
            <i class="fas fa-history mr-1"></i> Logs
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="mb-1">Name</label>
                        <input class="form-control" wire:model.defer="name" placeholder="e.g. Urgent requests notify + assign">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="mb-1">Trigger</label>
                        <select class="form-control" wire:model.defer="trigger">
                            @foreach($triggerOptions as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tip: scheduled triggers fire via the scheduler.</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="mb-1">Description</label>
                <textarea class="form-control" rows="2" wire:model.defer="description" placeholder="Optional"></textarea>
            </div>

            <div class="d-flex flex-wrap" style="gap: 12px;">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" wire:model.defer="is_active">
                    <label class="custom-control-label" for="is_active">Enabled</label>
                </div>
                <div style="min-width: 220px;">
                    <label class="mb-1">Sort order</label>
                    <input type="number" class="form-control" wire:model.defer="sort_order">
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">IF (Conditions)</h5>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="text-muted">Combine with</span>
                    <select class="form-control form-control-sm" style="width: 90px;" wire:model.defer="conditions_operator">
                        <option value="and">AND</option>
                        <option value="or">OR</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" wire:click="addCondition">
                        <i class="fas fa-plus mr-1"></i> Add
                    </button>
                </div>
            </div>

            <div class="mt-2">
                @foreach($conditions as $i => $c)
                    <div class="border rounded p-2 mb-2">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label class="mb-1">Field (dot path)</label>
                                <input class="form-control" wire:model.defer="conditions.{{ $i }}.field" placeholder="request.priority">
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Operator</label>
                                <select class="form-control" wire:model.defer="conditions.{{ $i }}.operator">
                                    <option value="equals">equals</option>
                                    <option value="not_equals">not_equals</option>
                                    <option value="in">in</option>
                                    <option value="contains">contains</option>
                                    <option value="gt">gt</option>
                                    <option value="gte">gte</option>
                                    <option value="lt">lt</option>
                                    <option value="lte">lte</option>
                                    <option value="is_true">is_true</option>
                                    <option value="is_false">is_false</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Value</label>
                                <input class="form-control" wire:model.defer="conditions.{{ $i }}.value" placeholder="urgent">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-outline-danger btn-sm" wire:click="removeCondition({{ $i }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-muted small mt-1">Examples: <code>request.priority</code>, <code>invoice.status</code>, <code>client.tier</code>, <code>storage.percent</code></div>
                    </div>
                @endforeach
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">THEN (Actions)</h5>
                <button class="btn btn-sm btn-outline-primary" wire:click="addAction">
                    <i class="fas fa-plus mr-1"></i> Add
                </button>
            </div>

            <div class="mt-2">
                @foreach($actions as $i => $a)
                    <div class="border rounded p-2 mb-2">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="mb-1">Action</label>
                                <select class="form-control" wire:model.defer="actions.{{ $i }}.type">
                                    @foreach($actionOptions as $k => $label)
                                        <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="mb-1">Config (JSON-ish)</label>
                                <input class="form-control" wire:model.defer="actions.{{ $i }}.config.message" placeholder="Varies by action (quick fields)">
                                <small class="text-muted">
                                    Use placeholders like <code>{{ '{{request.id}}' }}</code>, <code>{{ '{{client.email}}' }}</code>.
                                </small>
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-outline-danger btn-sm" wire:click="removeAction({{ $i }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Common quick configs --}}
                        @if(($a['type'] ?? '') === 'assign_request')
                            <div class="mt-2">
                                <label class="mb-1">Assign to staff</label>
                                <select class="form-control" wire:model.defer="actions.{{ $i }}.config.user_id">
                                    <option value="">Select...</option>
                                    @foreach($staffOptions as $u)
                                        <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif(($a['type'] ?? '') === 'change_request_status')
                            <div class="mt-2">
                                <label class="mb-1">New status</label>
                                <select class="form-control" wire:model.defer="actions.{{ $i }}.config.status">
                                    <option value="pending">pending</option>
                                    <option value="in_review">in_review</option>
                                    <option value="approved">approved</option>
                                    <option value="in_progress">in_progress</option>
                                    <option value="on_hold">on_hold</option>
                                    <option value="completed">completed</option>
                                    <option value="cancelled">cancelled</option>
                                </select>
                            </div>
                        @elseif(($a['type'] ?? '') === 'send_email')
                            <div class="mt-2 row">
                                <div class="col-md-4">
                                    <label class="mb-1">To</label>
                                    <input class="form-control" wire:model.defer="actions.{{ $i }}.config.to" placeholder="client or email@...">
                                </div>
                                <div class="col-md-8">
                                    <label class="mb-1">Subject</label>
                                    <input class="form-control" wire:model.defer="actions.{{ $i }}.config.subject" placeholder="Reminder: invoice {{ '{{invoice.invoice_number}}' }}">
                                </div>
                                <div class="col-md-12 mt-2">
                                    <label class="mb-1">Body</label>
                                    <textarea class="form-control" rows="2" wire:model.defer="actions.{{ $i }}.config.body"></textarea>
                                </div>
                            </div>
                        @elseif(($a['type'] ?? '') === 'send_notification')
                            <div class="mt-2 row">
                                <div class="col-md-3">
                                    <label class="mb-1">Channel</label>
                                    <select class="form-control" wire:model.defer="actions.{{ $i }}.config.channel">
                                        <option value="slack">slack</option>
                                        <option value="teams">teams</option>
                                        <option value="sms">sms</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="mb-1">Webhook URL</label>
                                    <input class="form-control" wire:model.defer="actions.{{ $i }}.config.url" placeholder="https://hooks.slack.com/...">
                                </div>
                                <div class="col-md-12 mt-2">
                                    <label class="mb-1">Message</label>
                                    <input class="form-control" wire:model.defer="actions.{{ $i }}.config.message" placeholder="Urgent request {{ '{{request.id}}' }} created">
                                </div>
                            </div>
                        @elseif(($a['type'] ?? '') === 'trigger_webhook')
                            <div class="mt-2">
                                <label class="mb-1">Event name</label>
                                <input class="form-control" wire:model.defer="actions.{{ $i }}.config.event" placeholder="custom.event">
                            </div>
                        @elseif(($a['type'] ?? '') === 'update_client_tier')
                            <div class="mt-2">
                                <label class="mb-1">Tier</label>
                                <select class="form-control" wire:model.defer="actions.{{ $i }}.config.tier">
                                    <option value="basic">basic</option>
                                    <option value="standard">standard</option>
                                    <option value="premium">premium</option>
                                    <option value="enterprise">enterprise</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <hr>

            <div class="d-flex flex-wrap" style="gap: 10px;">
                <button class="btn btn-primary" wire:click="save">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
                <button class="btn btn-outline-secondary" wire:click="testRun">
                    <i class="fas fa-vial mr-1"></i> Test (sample data)
                </button>
            </div>

            @if(!empty($testResult))
                <div class="alert alert-info mt-3 mb-0">
                    <div><strong>Matched:</strong> {{ $testResult['matched'] ? 'yes' : 'no' }}</div>
                    <div class="text-muted">{{ $testResult['note'] ?? '' }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

