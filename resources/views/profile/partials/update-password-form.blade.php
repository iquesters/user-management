<section class="">
    <header class="">
        <p class="text-muted small">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <div class="w-100 row">
        <!-- Password Update Form -->
        <div class="col-6">
            <form method="post" action="{{ route('password.update') }}" class="" id="passwordUpdateForm">
                @csrf
                @method('put')

                <!-- Hidden username field for accessibility -->
                <div class="visually-hidden">
                    <label for="hidden_username">{{ __('Username') }}</label>
                    <input type="text" id="hidden_username" name="username" autocomplete="username" value="{{ auth()->user()->email ?? '' }}">
                </div>

                <!-- Current Password Field -->
                <div class="mb-3">
                    <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="update_password_current_password" name="current_password" autocomplete="current-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('current_password', 'updatePassword')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- New Password Field -->
                <div class="mb-3">
                    <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="update_password_password" name="password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <small id="updatePasswordHelp" class="form-text text-danger" style="display: none;">
                        Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character (@ $ ! % * ? &).
                    </small>
                    @error('password', 'updatePassword')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-3">
                    <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div id="updatePasswordMatch" class="text-danger small" style="display: none;"></div>
                    @error('password_confirmation', 'updatePassword')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Save Button and Status Message -->
                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-sm btn-outline-primary" id="updateSubmitButton">{{ __('Update') }}</button>

                    @if (session('status') === 'password-updated')
                    <p class="text-muted small mb-0" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)">
                        {{ __('Saved.') }}
                    </p>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-6">
            @include('userinterface::components.form',
            [
                'id' => '01K8ZNEC7RJ5ZQEATD5150H2K3'
            ])
        </div>
    </div>
</section>