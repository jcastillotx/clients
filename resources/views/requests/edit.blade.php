<x-app-layout>
    <x-slot name="header">Edit Request: {{ $request->title }}</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('requests.update', $request) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $request->title) }}"
                                   required>
                            @error('title')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="6" 
                                      class="form-control @error('description') is-invalid @enderror"
                                      required>{{ old('description', $request->description) }}</textarea>
                            @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select name="type" id="type" class="form-control">
                                        @foreach(config('client-portal.request_types') as $value => $label)
                                        <option value="{{ $value }}" {{ $request->type === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select name="priority" id="priority" class="form-control">
                                        @foreach(config('client-portal.request_priorities') as $value => $label)
                                        <option value="{{ $value }}" {{ $request->priority === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" 
                                           name="due_date" 
                                           id="due_date" 
                                           class="form-control"
                                           value="{{ old('due_date', $request->due_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update Request
                            </button>
                            <a href="{{ route('requests.show', $request) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
