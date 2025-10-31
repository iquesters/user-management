@extends(app('auth.layout'))

@section('content')
<div class="w-100 row">
    <div class="col-6">
        <form method="POST" 
            action="{{ route('ui.setup.store') }}" 
            enctype="multipart/form-data"
            id="setup-form"
            data-recaptcha-action="setup">
            @csrf

            <div class="mb-3">
                <label class="form-label">Application Name</label>
                <input type="text" name="app_name" class="form-control" value="{{ old('app_name') }}" required>
                @error('app_name') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*" required>
                @error('logo') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                @error('description') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            @include('usermanagement::components.recaptcha-field')

            <div class="d-flex justify-content-end align-items-center">
                <button type="submit" class="btn btn-sm btn-outline-info" id="setup-button">
                    Continue
                </button>
            </div>
        </form>
    </div>
    <div class="col-6">
        @include('userinterface::components.form',
        [
            'id' => 'setup-form'
        ])
    </div>
</div>
@endsection