@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)

@section('content')
<div class="">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 text-muted">Total {{ $roles->count() }} Roles</h5>
        <div class="d-flex justify-content-center align-items-center gap-2">
            <button type="button" 
                    class="btn btn-outline-primary btn-sm d-flex align-items-center shadow-sm rounded"
                    data-bs-toggle="modal" data-bs-target="#createRoleModal" @cannot('create-roles') disabled @endcannot
                    @cannot('create-roles') disabled @endcannot>
                <i class="fa-regular fa-fw fa-plus fs-5"></i>
                <span class="d-none d-md-inline-block ms-2">Role</span>
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="rolesTable" class="table table-sm table-striped table-bordered" 
               style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role Type</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td>
                        <a href="{{ route('roles.show', $role->id) }}" 
                           class="text-decoration-none">
                           {{ $role->name }}
                        </a>
                    </td>
                    <td>
                        NA
                    </td>
                    <td>
                        {{ $role->permissions->count() }}
                    </td>
                    <td>
                        NA
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-center align-items-center">
                            <a href="{{ route('roles.edit', $role) }}" 
                               class="btn btn-sm btn-outline-warning @cannot('edit-roles') disabled @endcannot"
                               @cannot('edit-roles') disabled @endcannot>
                                <i class="fas fa-fw fa-edit"></i>
                            </a>
                            @if ($role->name !== 'super-admin')
                            <form action="{{ route('roles.destroy', $role) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger @cannot('delete-roles') disabled @endcannot"
                                        @cannot('delete-roles') disabled @endcannot
                                        onclick="return confirm('Are you sure you want to delete this role?')">
                                    <i class="fas fa-fw fa-trash"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-muted">Cannot Delete</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoleModalLabel">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Role Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="roleName" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-outline-primary @cannot('create-roles') disabled @endcannot" @cannot('create-roles') disabled @endcannot>
                        Create
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#rolesTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "asc"]],
            "columnDefs": [
                { "width": "25%", "targets": 0 }, // Name
                { "width": "20%", "targets": 1 }, // Role Type
                { "width": "20%", "targets": 2 }, // Permissions
                { "width": "20%", "targets": 3 }, // Users
                { "width": "15%", "targets": 4, "orderable": false } // Actions
            ]
        });
    });
</script>
@endpush
@endsection