@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)

@section('content')
<div class="mb-2">
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fs-6 text-muted">Total {{ $users->count() }} Users</h5>
        <a href="{{ route('users.create') }}" 
         class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 shadow-sm rounded @cannot('create-users') disabled @endcannot"
                @cannot('create-users') disabled @endcannot>
            <i class="fa-regular fa-plus fs-6"></i>User
        </a>
    </div>

    <div class="">
        <table id="usersTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                        <span class="badge bg-primary">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        {{ $user->status }}
                    </td>
                    <td>
                        <!-- delete user -->
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>
                        <!-- @ can('manage-user') -->
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-dark rounded">
                            <i class="fas fa-fw fa-edit"></i>
                        </a>
                        <!-- @ endcan -->
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable();
    });
</script>
@endpush