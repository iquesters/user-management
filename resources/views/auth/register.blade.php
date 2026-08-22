@extends(app('auth.layout'))

@section('content')
@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $authConfig = ConfProvider::from(Module::USER_MGMT);
    $socialLoginConfig = $authConfig->social_login;
    $socialProviders = collect($socialLoginConfig->o_auth_providers ?? [])->filter(function ($provider) {
        return $provider->enabled ?? false;
    })->map(function ($provider, $name) {
        return [
            'name' => is_string($name) ? $name : ($provider->identifier ?? null),
            'config' => $provider,
        ];
    })->filter(function ($providerData) {
        return !empty($providerData['name']);
    })->unique('name')->values();
    $hasSocialLogin = ($socialLoginConfig->enabled ?? false) && $socialProviders->isNotEmpty();
    $hasWhatsAppLogin = ($socialLoginConfig->enabled ?? false) && (bool) (($authConfig->whatsapp_login->enabled ?? false));
    // The schema-driven form column has no recaptcha field yet, so submitting
    // it would always fail validation once recaptcha is turned on. Fall back
    // to the classic form alone until that's built.
    $recaptchaEnabled = $authConfig->recaptcha->enabled ?? false;
@endphp

<div class="w-100 row">
    <div class="col-6">
        <div id="register-classic-section">
            <form method="POST" action="{{ route('register') }}" id="register-form" data-recaptcha-action="register">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                    @error('email')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <div class="input-group">
                        <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                    <div class="input-group">
                        <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                @include('usermanagement::components.recaptcha-field')

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a class="text-decoration-none text-info" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>

                    <button type="submit" class="btn btn-sm btn-outline-info" id="register-button">
                        {{ __('Register') }}
                    </button>
                </div>
            </form>
        </div>

        <div id="register-whatsapp-panel" class="card border-0 bg-light-subtle d-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <h6 class="mb-1">Register with WhatsApp OTP</h6>
                        <p class="text-muted small mb-0">
                            Verify your WhatsApp number first, then provide your name to create the account.
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="whatsapp-register-back">
                        Or use classic method
                    </button>
                </div>

                {{-- Intentional inline auth-state structure for registration; @todo move the WhatsApp registration UI behavior into dedicated auth assets later. --}}
                <form id="whatsapp-register-form" class="d-grid gap-3">
                    <div>
                        <label for="register-whatsapp-phone" class="form-label">WhatsApp number</label>
                        <input id="register-whatsapp-phone" type="tel" class="form-control" placeholder="+919876543210" inputmode="tel" autocomplete="tel">
                    </div>

                    <div id="register-whatsapp-otp-wrap" class="d-none">
                        <label for="register-whatsapp-otp" class="form-label">Verification code</label>
                        <input id="register-whatsapp-otp" type="text" class="form-control" placeholder="Enter OTP" inputmode="numeric" autocomplete="one-time-code">
                    </div>

                    <div id="register-whatsapp-name-wrap" class="d-none">
                        <label for="register-whatsapp-name" class="form-label">Name</label>
                        <input id="register-whatsapp-name" type="text" class="form-control" placeholder="Enter your full name" autocomplete="name">
                    </div>

                    <div id="register-whatsapp-email-wrap" class="d-none">
                        <label for="register-whatsapp-email" class="form-label">Email</label>
                        <input id="register-whatsapp-email" type="email" class="form-control" placeholder="Enter your email address" autocomplete="email">
                    </div>

                    <div id="register-whatsapp-feedback" class="small text-muted" aria-live="polite"></div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="register-whatsapp-send">
                            Send OTP on WhatsApp
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success d-none" id="register-whatsapp-verify">
                            Verify number
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="register-whatsapp-resend">
                            Resend OTP
                        </button>
                        <button type="button" class="btn btn-sm btn-primary d-none" id="register-whatsapp-complete">
                            Create account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="register-alternate-auth">
            @if ($hasSocialLogin || $hasWhatsAppLogin)
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="mx-2 text-muted">or</span>
                    <hr class="flex-grow-1">
                </div>
            @endif

            @include('usermanagement::components.social-login-section', ['showDivider' => false])
            @include('usermanagement::components.whatsapp-register-section')
        </div>
    </div>
    @unless ($recaptchaEnabled)
    <div class="col-6">
        @include('userinterface::components.form',
        [
            'id' => 'register'
        ])
    </div>
    @endunless
</div>

@push('scripts')
    <script>
        // @todo Move WhatsApp registration auth behavior into dedicated assets when the shared auth JS module is extracted.
        document.addEventListener('DOMContentLoaded', function () {
            const phoneInput = document.getElementById('register-whatsapp-phone');
            const otpInput = document.getElementById('register-whatsapp-otp');
            const nameInput = document.getElementById('register-whatsapp-name');
            const emailInput = document.getElementById('register-whatsapp-email');
            const otpWrap = document.getElementById('register-whatsapp-otp-wrap');
            const nameWrap = document.getElementById('register-whatsapp-name-wrap');
            const emailWrap = document.getElementById('register-whatsapp-email-wrap');
            const feedback = document.getElementById('register-whatsapp-feedback');
            const sendButton = document.getElementById('register-whatsapp-send');
            const verifyButton = document.getElementById('register-whatsapp-verify');
            const resendButton = document.getElementById('register-whatsapp-resend');
            const completeButton = document.getElementById('register-whatsapp-complete');

            if (!phoneInput || !sendButton || !verifyButton || !resendButton || !completeButton) {
                return;
            }

            const csrfToken = @json(csrf_token());
            const endpoints = {
                send: @json(route('whatsapp.register.send')),
                verify: @json(route('whatsapp.register.verify')),
                complete: @json(route('whatsapp.register.complete'))
            };

            function setFeedback(message, tone) {
                feedback.textContent = message || '';
                feedback.classList.remove('text-muted', 'text-danger', 'text-success');
                feedback.classList.add(tone === 'error' ? 'text-danger' : tone === 'success' ? 'text-success' : 'text-muted');
            }

            function setLoading(button, isLoading, label) {
                if (isLoading) {
                    button.dataset.originalLabel = button.textContent;
                    button.disabled = true;
                    button.textContent = label;
                    return;
                }

                button.disabled = false;
                button.textContent = button.dataset.originalLabel || button.textContent;
            }

            async function postJson(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(function () {
                    return { success: false, message: 'Unexpected response from the server.' };
                });

                if (!response.ok) {
                    throw data;
                }

                return data;
            }

            sendButton.addEventListener('click', async function () {
                setLoading(sendButton, true, 'Sending...');
                try {
                    const data = await postJson(endpoints.send, { phone: phoneInput.value.trim() });
                    otpWrap.classList.remove('d-none');
                    verifyButton.classList.remove('d-none');
                    resendButton.classList.remove('d-none');
                    setFeedback(data.message, 'success');
                    otpInput.focus();
                } catch (error) {
                    setFeedback(error.message || 'Unable to send verification code.', 'error');
                } finally {
                    setLoading(sendButton, false, 'Sending...');
                }
            });

            resendButton.addEventListener('click', async function () {
                setLoading(resendButton, true, 'Resending...');
                try {
                    const data = await postJson(endpoints.send, { phone: phoneInput.value.trim() });
                    setFeedback(data.message, 'success');
                } catch (error) {
                    setFeedback(error.message || 'Unable to resend verification code.', 'error');
                } finally {
                    setLoading(resendButton, false, 'Resending...');
                }
            });

            verifyButton.addEventListener('click', async function () {
                setLoading(verifyButton, true, 'Verifying...');
                try {
                    const data = await postJson(endpoints.verify, {
                        phone: phoneInput.value.trim(),
                        otp: otpInput.value.trim()
                    });
                    nameWrap.classList.remove('d-none');
                    emailWrap.classList.remove('d-none');
                    completeButton.classList.remove('d-none');
                    setFeedback(data.message, 'success');
                    nameInput.focus();
                } catch (error) {
                    setFeedback(error.message || 'Verification failed.', 'error');
                } finally {
                    setLoading(verifyButton, false, 'Verifying...');
                }
            });

            completeButton.addEventListener('click', async function () {
                setLoading(completeButton, true, 'Creating...');
                try {
                    const data = await postJson(endpoints.complete, {
                        name: nameInput.value.trim(),
                        email: emailInput.value.trim()
                    });
                    setFeedback(data.message, 'success');
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    window.location.reload();
                } catch (error) {
                    setFeedback(error.message || 'Unable to complete registration.', 'error');
                } finally {
                    setLoading(completeButton, false, 'Creating...');
                }
            });
        });
    </script>
@endpush
@endsection
