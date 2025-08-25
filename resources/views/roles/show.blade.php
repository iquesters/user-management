@extends('usermanagement::layouts.roles')

@section('role-content')
    <h5 class="fs-6">Role: {{ $role->name }}</h5>

    <p>Total {{ $permissions->count() }} Permissions:</p>
    <ul>
        @foreach ($permissions as $permission)
            <li>{{ $permission->name }}</li>
        @endforeach
    </ul>
@endsection
