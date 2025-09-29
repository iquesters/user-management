@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)

@section('content')
<div class="">
    <div class="">
        <h6 class="mb-3 text-muted">Edit Role</h6>
        <div class="">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <!-- Role Name -->
                <div class="mb-2">
                    <label for="name" class="form-label">Role Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name) }}" required autofocus>
                    @error('name')
                    <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Permissions -->
                <x-usermanagement::permissions-edit 
                    :permissions="$permissions" 
                    :selectedPermissions="$role->permissions->pluck('name')->toArray()" 
                />

                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-dark me-3">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-outline-primary @cannot('edit-roles') disabled @endcannot"
                            @cannot('edit-roles') disabled @endcannot>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection