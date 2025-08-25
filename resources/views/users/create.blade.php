@extends(config('usermanagement.layout_app'))

@section('content')
<div class="">
    {{-- <h1 class="mb-3 fs-6">Create User</h1> --}}
    <form method="POST" action="{{ route('users.store') }}">
        <div class="card">
            <div class="card-header fw-bold">Create New User</div>
            <div class="card-body">
                @csrf

                <!-- User Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                    <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                    <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" {{ !isset($user) ? 'required' : '' }}>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <small id="passwordHelp" class="form-text text-danger" style="display: none;">
                        Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character (@ $ ! % * ? &).
                    </small>
                    @error('password')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ !isset($user) ? 'required' : '' }}>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="text-danger small" style="display: none;"></div>
                </div>

                <!-- Roles -->
                <div class="mb-4">
                    <label class="form-label">Assign Roles</label>
                    <div class="row">
                        @foreach($roles as $role)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="role-{{ $role->name }}" name="roles[]" value="{{ $role->name }}">
                                <label class="form-check-label" for="role-{{ $role->name }}">{{ $role->name }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('roles')
                    <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-end">
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-dark me-3">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-outline-primary @cannot('create-users') disabled @endcannot"
                        @cannot('create-users') disabled @endcannot>Create User</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection