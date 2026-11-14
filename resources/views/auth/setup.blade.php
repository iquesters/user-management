@extends(app('auth.layout'))

@php
    // auth_form_type conf: classic (default) | schema | both
    $showClassic = \Iquesters\UserManagement\Support\AuthFormSchema::showsClassic();
    $showSchema = \Iquesters\UserManagement\Support\AuthFormSchema::showsSchema();
    $formCol = $showClassic && $showSchema ? 'col-6' : 'col-12';
@endphp
@unless ($showClassic && $showSchema)
    @section('auth-card-class', 'col-md-7 col-lg-4')
@endunless

@section('content')
<div class="w-100 row">
    @if ($showClassic)
    <div class="{{ $formCol }}">
        <form method="POST"
            action="{{ route('ui.setup.store') }}" 
            enctype="multipart/form-data"
            id="setupClassicForm"
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
                <small id="logo-error" class="text-danger d-none"></small>
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
    @endif
    @if ($showSchema)
    <div class="{{ $formCol }}">
        @include('userinterface::components.form', ['id' => 'setup-form'])
    </div>
    @endif
</div>
@endsection

@if ($showClassic)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('setupClassicForm');
    const logoInput = form.querySelector('input[name="logo"]');
    const errorMsg = document.getElementById('logo-error');
    const submitBtn = document.getElementById('setup-button');
    const maxSize = 2 * 1024 * 1024; // 2 MB

    // Function to validate the logo file
    const validateLogo = () => {
        const file = logoInput.files[0];
        errorMsg.classList.add('d-none');
        errorMsg.textContent = '';
        submitBtn.disabled = false; // reset before checking

        if (!file) return true;

        if (!file.type.startsWith('image/')) {
            errorMsg.textContent = 'Please upload a valid image file.';
            errorMsg.classList.remove('d-none');
            logoInput.value = '';
            submitBtn.disabled = true;
            return false;
        }

        if (file.size > maxSize) {
            errorMsg.textContent = 'File size must not exceed 2 MB.';
            errorMsg.classList.remove('d-none');
            logoInput.value = '';
            submitBtn.disabled = true;
            return false;
        }

        // Valid file
        return true;
    };

    // Check file when changed
    logoInput.addEventListener('change', validateLogo);

    // Disable button on invalid submit
    form.addEventListener('submit', (e) => {
        const isValid = validateLogo();
        if (!isValid) {
            e.preventDefault();
            return;
        }

        // Disable button to prevent double-submit
        submitBtn.disabled = true;
        submitBtn.textContent = 'Please wait...';
    });
});
</script>
@endpush
@endif