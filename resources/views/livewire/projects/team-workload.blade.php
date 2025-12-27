<x-app-layout>
    <x-slot name="header">Team workload</x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-1"></i> Team workload</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>To do</th>
                        <th>In progress</th>
                        <th>Blocked</th>
                        <th>Done</th>
                        <th>Hours this week</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r['user']->name }}</td>
                            <td>{{ $r['todo'] }}</td>
                            <td>{{ $r['in_progress'] }}</td>
                            <td>{{ $r['blocked'] }}</td>
                            <td>{{ $r['done'] }}</td>
                            <td>{{ $r['hours_this_week'] }}</td>
                        </tr>
                    @endforeach
                    @if(empty($rows))
                        <tr><td colspan="6" class="text-muted p-3">No users found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

