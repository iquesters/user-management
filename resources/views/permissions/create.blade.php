@extends(config('usermanagement.layout_app'))

@section('content')
<div class="">
    <h5 class="fs-6 text-muted mb-4">Create New Permission</h5>

    <div class="">
        <div class="">
            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf

                <!-- Permission Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Permission Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                    <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-dark me-3">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-outline-primary @cannot('create-permissions') disabled @endcannot"
                        @cannot('create-permissions') disabled @endcannot>Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection