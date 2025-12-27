<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Team workload</h3>
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
                </tbody>
            </table>
        </div>
    </div>
</div>

