@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)
@section('content')
<div class="">
    <h4 class="fs-6 text-muted">Edit Permission</h5>
    <div class="">
        <form method="POST" action="{{ route('permissions.update', $permission) }}">
            @csrf
            @method('PUT')

            <!-- Permission Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Permission Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $permission->name) }}" required autofocus>
                @error('name')
                <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-dark me-3">Cancel</a>
                <button type="submit" class="btn btn-sm btn-outline-primary @cannot('edit-permissions') disabled @endcannot"
                @cannot('edit-permissions') disabled @endcannot>Update</button>
            </div>
        </form>
    </div>
</div>
@endsection