@extends('usermanagement::layouts.roles')

@section('role-content')
    <div id="permissions-section">
        <!-- View Mode -->
        <div id="view-permissions">
            <div class="mt-4 text-end">
                <button
                    class="btn btn-sm btn-outline-dark gap-2"
                    onclick="toggleEdit(true)"
                    @cannot('edit-roles') disabled @endcannot
                >
                    <i class="fas fa-fw fa-edit"></i> Edit
                </button>
            </div>
            <x-usermanagement::permissions-edit
                :permissions="$permissions"
                :selectedPermissions="$rolePermissions->pluck('name')->toArray()"
                mode="view"
            />
        </div>

        <!-- Edit Mode -->
        <div id="edit-permissions" style="display: none;">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <p>Edit mode is on, select permissions and update.</p>

                <!-- Role Name -->
                <div class="mb-2">
                    <label for="name" class="form-label">Role Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        required
                        autofocus
                    >
                    @error('name')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Permissions -->
                <x-usermanagement::permissions-edit
                    :permissions="$permissions"
                    :selectedPermissions="$rolePermissions->pluck('name')->toArray()"
                />

                <div class="d-flex justify-content-end mt-4">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-dark me-3"
                        onclick="toggleEdit(false)"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn btn-sm btn-outline-primary"
                        @cannot('edit-roles') disabled @endcannot
                    >
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleEdit(editing) {
            document.getElementById('view-permissions').style.display = editing ? 'none' : 'block';
            document.getElementById('edit-permissions').style.display = editing ? 'block' : 'none';
        }
    </script>
@endsection