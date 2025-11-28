<section class="">
    <header class="">
        {{-- <h2 class="fs-6 card-title">
            {{ __('Profile Information') }}
        </h2> --}}
        <p class="text-muted small">
            {{ __("Update your account profile information and email address.") }}
        </p>
    </header>

    <!-- Verification Form -->
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <div class="w-100 row">
        <div class="col-6">

            <!-- Email Field -->
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <p class="form-control-plaintext">{{ $user->email }}</p>
                    {{-- <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"> --}}
                    {{-- @error('email') --}}
                    {{-- <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror --}}

                    <!-- Email Verification Section -->
                    {{-- @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()) --}}
                    {{-- <div class="mt-3">
                        <p class="text-muted small">
                            {{ __('Your email address is unverified.') }}
                            <button form="send-verification" class="btn btn-sm btn-outline-dark p-0 text-decoration-none">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                        <p class="text-success small mt-2">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                        @endif
                    </div> --}}
                    {{-- @endif --}}
                </div>


            <!-- Profile Update Form -->
            <form method="post" action="{{ route('profile.update') }}" class="">
                @csrf
                @method('patch')

                <!-- Name Field -->
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                



                <!-- Theme Selection -->
                <div class="mb-3">
                    <label for="theme" class="form-label">{{ __('Theme') }}</label>
                    <select id="theme" name="theme" class="form-select">
                        <option value="">{{ __('Select Theme') }}</option>
                        @foreach($themes as $theme)
                            <option value="{{ $theme->key }}" {{ (old('theme', $userMetas['theme'] ?? '') == $theme->key) ? 'selected' : '' }}>
                                {{ $theme->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('theme')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>





                <!-- Save Button and Status Message -->
                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Update') }}</button>

                    @if (session('status') === 'profile-updated')
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
                'id' => '01K9EWNSPK0M9J9241NB33YKD7'
            ])
        </div>
    </div>
</section>