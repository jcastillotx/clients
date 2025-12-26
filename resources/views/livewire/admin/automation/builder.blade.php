<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">{{ $ruleId ? 'Edit automation' : 'New automation' }}</h2>
                <div class="text-muted">Build if/then logic with multiple conditions and actions.</div>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.automation.index') }}" class="btn btn-outline-secondary">Back</a>
                <a href="{{ route('admin.automation.logs') }}" class="btn btn-outline-secondary">Logs</a>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rule</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" wire:model.defer="name" placeholder="e.g. Urgent requests → Slack + assign">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="2" wire:model.defer="description"></textarea>
                        @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label">Trigger</label>
                            <select class="form-select" wire:model="trigger">
                                @foreach($triggerOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                                @endforeach
                            </select>
                            @error('trigger') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Order</label>
                            <input type="number" class="form-control" wire:model.defer="runOrder">
                            @error('runOrder') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Active</label>
                            <div>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="isActive">
                                    <span class="form-check-label">{{ $isActive ? 'Enabled' : 'Disabled' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Conditions</h3>
                            <div class="ms-auto">
                                <select class="form-select form-select-sm" wire:model="conditionsOp" style="width: 110px;">
                                    <option value="and">AND</option>
                                    <option value="or">OR</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach($conditions as $i => $c)
                                <div class="row g-2 align-items-end mb-2">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label">Path</label>
                                        <input class="form-control" wire:model.defer="conditions.{{ $i }}.path" placeholder="e.g. request.priority">
                                        @error("conditions.$i.path") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">Operator</label>
                                        <select class="form-select" wire:model.defer="conditions.{{ $i }}.operator">
                                            @foreach($operators as $op)
                                                <option value="{{ $op }}">{{ $op }}</option>
                                            @endforeach
                                        </select>
                                        @error("conditions.$i.operator") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label">Value</label>
                                        <input class="form-control" wire:model.defer="conditions.{{ $i }}.value" placeholder="(optional)">
                                    </div>
                                    <div class="col-12 col-md-1 text-end">
                                        <button class="btn btn-outline-danger" type="button" wire:click="removeCondition({{ $i }})">×</button>
                                    </div>
                                </div>
                            @endforeach

                            <button class="btn btn-outline-primary" type="button" wire:click="addCondition">Add condition</button>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Actions</h3>
                        </div>
                        <div class="card-body">
                            @foreach($actions as $i => $a)
                                <div class="border rounded p-3 mb-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Action type</label>
                                            <select class="form-select" wire:model="actions.{{ $i }}.type">
                                                @foreach($actionTypes as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error("actions.$i.type") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <label class="form-label">Parameters (JSON)</label>
                                            <input class="form-control" wire:model.defer="actions.{{ $i }}.params_json" placeholder='e.g. {"channel":"slack","message":"..."}'>
                                            <div class="text-muted small mt-1">Tip: use template vars like <code>{{'{{request.title}}'}}</code></div>
                                        </div>
                                        <div class="col-12 col-md-1 text-end">
                                            <button class="btn btn-outline-danger" type="button" wire:click="removeAction({{ $i }})">×</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <button class="btn btn-outline-primary" type="button" wire:click="addAction">Add action</button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary" wire:click="save">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Test automation (dry run)</h3>
                </div>
                <div class="card-body">
                    <label class="form-label">Sample payload JSON</label>
                    <textarea class="form-control font-monospace" rows="10" wire:model.defer="samplePayloadJson"></textarea>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary" wire:click="test">Run test</button>
                    </div>

                    @if($testResult)
                        <div class="mt-3">
                            <div class="fw-bold">Result</div>
                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($testResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                    @endif

                    <div class="text-muted small">
                        Actions run in dry-run mode (no side effects) and will still be logged when executed in real triggers.
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Action params help</h3>
                </div>
                <div class="card-body">
                    <div class="text-muted small">
                        Use JSON per action:
                        <ul class="mb-0">
                            <li><code>send_notification</code>: <code>{"channel":"slack|teams","message":"..."}</code></li>
                            <li><code>send_email</code>: <code>{"to":"admin|client|email","email":"x@y.com","subject":"...","body":"..."}</code></li>
                            <li><code>assign_request</code>: <code>{"request_id":123,"assignee_role":"staff"}</code> or <code>{"assignee_user_id":5}</code></li>
                            <li><code>create_invoice</code>: <code>{"client_id":1,"request_id":123,"status":"draft"}</code></li>
                            <li><code>trigger_webhook</code>: <code>{"client_id":1,"event":"request.created","data":{...}}</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

