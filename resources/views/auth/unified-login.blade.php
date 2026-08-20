@extends(app('auth.layout'))

@section('content')
@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $authConfig = ConfProvider::from(Module::USER_MGMT);
    $socialLoginConfig = $authConfig->social_login;
    $socialProviders = collect($socialLoginConfig->o_auth_providers ?? [])->filter(function ($provider) {
        return $provider->enabled ?? false;
    });
    $hasSocialLogin = ($socialLoginConfig->enabled ?? false) && $socialProviders->isNotEmpty();
@endphp

<div class="w-100 row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h4 class="mb-2">Sign in or create your account</h4>
                    <p class="text-muted mb-0">Start with your email address or phone number. We will guide you to the right next step.</p>
                </div>

                {{-- Intentional inline unified-auth state machine for the package login screen; @todo move this temporary auth UI behavior into dedicated assets once the shared auth module is extracted. --}}
                <form id="unified-identify-form" class="d-grid gap-3">
                    <div>
                        <label for="unified-country-dial-code" class="form-label">Country code</label>
                        <select id="unified-country-dial-code" class="form-select" autocomplete="tel-country-code">
                            @foreach ($countryDialCodes as $countryDialCode)
                                <option value="{{ $countryDialCode['dial_code'] }}">{{ $countryDialCode['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unified-identifier" class="form-label">Email or phone</label>
                        <input
                            id="unified-identifier"
                            type="text"
                            class="form-control"
                            placeholder="name@example.com or 9876543210"
                            autocomplete="username"
                            autofocus
                        >
                    </div>

                    <div id="unified-feedback" class="small text-muted" aria-live="polite"></div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-info" id="unified-identify-button">Continue</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="unified-reset-button">Use a different email or phone</button>
                    </div>
                </form>

                <div id="unified-password-section" class="d-none mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Login with password</h6>
                        <button type="button" class="btn btn-sm btn-link p-0 d-none" id="unified-switch-to-otp">Or verify with OTP instead</button>
                    </div>
                    <form method="POST" action="{{ route('login') }}" id="unified-password-form" data-recaptcha-action="login" class="d-grid gap-3">
                        @csrf
                        <input type="hidden" name="email" id="unified-password-email">
                        <div>
                            <label for="unified-password" class="form-label">Password</label>
                            <input id="unified-password" class="form-control" type="password" name="password" autocomplete="current-password">
                        </div>
                        @include('usermanagement::components.recaptcha-field')
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="text-decoration-none text-info" href="{{ route('password.request') }}">Forgot password?</a>
                            <button type="submit" class="btn btn-sm btn-outline-info">Log in</button>
                        </div>
                    </form>
                </div>

                <div id="unified-otp-section" class="d-none mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Login or verify with OTP</h6>
                        <button type="button" class="btn btn-sm btn-link p-0 d-none" id="unified-switch-to-password">Or login with password instead</button>
                    </div>
                    <div class="d-grid gap-3">
                        <div>
                            <label for="unified-otp" class="form-label">Verification code</label>
                            <input id="unified-otp" type="text" class="form-control" placeholder="Enter OTP" inputmode="numeric" autocomplete="one-time-code">
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-success" id="unified-send-otp-button">Send OTP</button>
                            <button type="button" class="btn btn-sm btn-outline-success d-none" id="unified-verify-otp-button">Verify OTP</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="unified-resend-otp-button">Resend OTP</button>
                        </div>
                    </div>
                </div>

                <div id="unified-registration-section" class="d-none mt-4">
                    <h6 class="mb-2">Complete your registration</h6>
                    <form id="unified-registration-form" class="d-grid gap-3">
                        <div id="unified-registration-fields" class="d-grid gap-3"></div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-primary" id="unified-complete-registration-button">Create account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($hasSocialLogin)
            <div class="mt-4">
                @include('usermanagement::components.social-login-section')
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        // @todo Move the temporary unified auth JavaScript into dedicated auth assets when the package auth UI module is extracted.
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = @json(csrf_token());
            const endpoints = {
                country: @json(route('auth.unified.country')),
                identify: @json(route('auth.unified.identify')),
                sendOtp: @json(route('auth.unified.otp.send')),
                verifyOtp: @json(route('auth.unified.otp.verify')),
                resendOtp: @json(route('auth.unified.otp.resend')),
                completeRegistration: @json(route('auth.unified.register.complete'))
            };

            const elements = {
                countryDialCode: document.getElementById('unified-country-dial-code'),
                identifier: document.getElementById('unified-identifier'),
                feedback: document.getElementById('unified-feedback'),
                identifyButton: document.getElementById('unified-identify-button'),
                resetButton: document.getElementById('unified-reset-button'),
                passwordSection: document.getElementById('unified-password-section'),
                passwordEmail: document.getElementById('unified-password-email'),
                otpSection: document.getElementById('unified-otp-section'),
                otpInput: document.getElementById('unified-otp'),
                sendOtpButton: document.getElementById('unified-send-otp-button'),
                verifyOtpButton: document.getElementById('unified-verify-otp-button'),
                resendOtpButton: document.getElementById('unified-resend-otp-button'),
                switchToOtpButton: document.getElementById('unified-switch-to-otp'),
                switchToPasswordButton: document.getElementById('unified-switch-to-password'),
                registrationSection: document.getElementById('unified-registration-section'),
                registrationForm: document.getElementById('unified-registration-form'),
                registrationFields: document.getElementById('unified-registration-fields'),
                completeRegistrationButton: document.getElementById('unified-complete-registration-button')
            };

            const state = {
                flowToken: null,
                identifierType: null,
                identifier: null,
                deliveryChannel: null,
                status: null,
                hasPasswordOption: false,
                otpSent: false,
                cooldownRemaining: 0,
                cooldownTimer: null
            };

            function log(level, message, context) {
                const payload = context || {};
                if (level === 'error') {
                    console.error('[UnifiedAuth]', message, payload);
                    return;
                }

                if (level === 'warn') {
                    console.warn('[UnifiedAuth]', message, payload);
                    return;
                }

                console.info('[UnifiedAuth]', message, payload);
            }

            function setFeedback(message, tone) {
                elements.feedback.textContent = message || '';
                elements.feedback.classList.remove('text-muted', 'text-danger', 'text-success');
                elements.feedback.classList.add(tone === 'error' ? 'text-danger' : tone === 'success' ? 'text-success' : 'text-muted');
            }

            function setLoading(button, isLoading, label) {
                if (!button) {
                    return;
                }

                if (isLoading) {
                    button.dataset.originalLabel = button.textContent;
                    button.disabled = true;
                    button.textContent = label;
                    return;
                }

                button.disabled = false;
                button.textContent = button.dataset.originalLabel || button.textContent;
            }

            function setOtpDispatched(isDispatched) {
                elements.sendOtpButton.classList.toggle('d-none', isDispatched);
                elements.verifyOtpButton.classList.toggle('d-none', !isDispatched);
                elements.resendOtpButton.classList.toggle('d-none', !isDispatched);
            }

            function resetPanels() {
                elements.passwordSection.classList.add('d-none');
                elements.otpSection.classList.add('d-none');
                elements.registrationSection.classList.add('d-none');
                elements.switchToOtpButton.classList.add('d-none');
                elements.switchToPasswordButton.classList.add('d-none');
                elements.registrationFields.innerHTML = '';
                elements.otpInput.value = '';
                state.otpSent = false;
                setOtpDispatched(false);
            }

            function showPasswordPanel() {
                elements.passwordSection.classList.remove('d-none');
                elements.otpSection.classList.add('d-none');
            }

            function showOtpPanel() {
                elements.otpSection.classList.remove('d-none');
                setOtpDispatched(state.otpSent);
                elements.passwordSection.classList.add('d-none');

                if (!state.otpSent) {
                    sendOtp(endpoints.sendOtp, 'send');
                }
            }

            function resetState() {
                state.flowToken = null;
                state.identifierType = null;
                state.identifier = null;
                state.deliveryChannel = null;
                state.status = null;
                state.hasPasswordOption = false;
                resetPanels();
                setFeedback('', 'muted');
                elements.resetButton.classList.add('d-none');
                elements.identifyButton.classList.remove('d-none');
                elements.identifier.disabled = false;
                elements.countryDialCode.disabled = false;
                elements.identifier.value = '';
                elements.identifier.focus();
                log('info', 'Unified auth state reset.', {});
            }

            function startCooldown(seconds) {
                state.cooldownRemaining = Number(seconds || 0);
                elements.resendOtpButton.disabled = true;

                if (state.cooldownTimer) {
                    window.clearInterval(state.cooldownTimer);
                }

                state.cooldownTimer = window.setInterval(function () {
                    state.cooldownRemaining -= 1;

                    if (state.cooldownRemaining <= 0) {
                        window.clearInterval(state.cooldownTimer);
                        state.cooldownTimer = null;
                        elements.resendOtpButton.disabled = false;
                        elements.resendOtpButton.textContent = 'Resend OTP';
                        return;
                    }

                    elements.resendOtpButton.textContent = 'Resend OTP (' + state.cooldownRemaining + 's)';
                }, 1000);

                elements.resendOtpButton.textContent = 'Resend OTP (' + state.cooldownRemaining + 's)';
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

            function otpPayload() {
                return {
                    flow_token: state.flowToken,
                    identifier_type: state.identifierType,
                    identifier: state.identifier,
                    delivery_channel: state.deliveryChannel
                };
            }

            function renderRegistrationFields(fields) {
                elements.registrationFields.innerHTML = '';

                fields.forEach(function (field) {
                    const wrap = document.createElement('div');
                    const label = document.createElement('label');
                    const input = document.createElement('input');

                    label.className = 'form-label';
                    label.setAttribute('for', 'field-' + field.identifier);
                    label.textContent = field.label;

                    input.className = 'form-control';
                    input.id = 'field-' + field.identifier;
                    input.name = 'fields[' + field.identifier + ']';
                    input.type = field.field_type === 'date' ? 'date' : field.field_type === 'email' ? 'email' : 'text';
                    input.value = field.default_value || '';
                    input.required = Boolean(field.required);

                    wrap.appendChild(label);
                    wrap.appendChild(input);
                    elements.registrationFields.appendChild(wrap);
                });
            }

            async function detectCountry() {
                try {
                    const response = await fetch(endpoints.country, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();

                    if (data && data.dial_code) {
                        elements.countryDialCode.value = data.dial_code;
                        log('info', 'Loaded default country dial code.', data);
                    }
                } catch (error) {
                    log('warn', 'Unable to load default country dial code.', {});
                }
            }

            async function identify() {
                const identifier = elements.identifier.value.trim();

                if (!identifier) {
                    setFeedback('Enter your email address or phone number first.', 'error');
                    return;
                }

                setLoading(elements.identifyButton, true, 'Checking...');
                setFeedback('Checking your account details.', 'muted');
                resetPanels();

                try {
                    const data = await postJson(endpoints.identify, {
                        identifier: identifier,
                        country_dial_code: elements.countryDialCode.value
                    });

                    state.identifierType = data.identifier_type;
                    state.flowToken = data.flow_token;
                    state.identifier = data.normalized_identifier;
                    state.deliveryChannel = data.delivery_channel;
                    state.status = data.status;
                    state.hasPasswordOption = (data.available_login_methods || []).includes('password') && Boolean(data.password_login_email);

                    elements.resetButton.classList.remove('d-none');
                    elements.identifyButton.classList.add('d-none');
                    elements.identifier.disabled = true;
                    elements.countryDialCode.disabled = true;

                    if (state.hasPasswordOption) {
                        elements.passwordEmail.value = data.password_login_email;
                        elements.switchToOtpButton.classList.remove('d-none');
                        elements.switchToPasswordButton.classList.remove('d-none');
                        showPasswordPanel();
                    } else {
                        showOtpPanel();
                    }

                    setFeedback(data.status === 'existing'
                        ? 'Account found. Continue with password or request an OTP.'
                        : 'No account found yet. Verify this identifier to create one.', 'success');
                    log('info', 'Unified auth identifier resolved.', data);
                } catch (error) {
                    setFeedback(error.message || 'Unable to process that identifier right now.', 'error');
                    log('error', 'Unified auth identify failed.', { error: error.message || 'unknown_error' });
                } finally {
                    setLoading(elements.identifyButton, false, 'Checking...');
                }
            }

            async function sendOtp(url, operation) {
                if (!state.identifierType || !state.identifier || !state.deliveryChannel) {
                    setFeedback('Start with your identifier first.', 'error');
                    return;
                }

                setLoading(operation === 'send' ? elements.sendOtpButton : elements.resendOtpButton, true, operation === 'send' ? 'Sending...' : 'Resending...');
                setFeedback('Preparing your verification code.', 'muted');

                try {
                    const data = await postJson(url, otpPayload());
                    setFeedback(data.message || 'Verification code sent.', 'success');
                    state.otpSent = true;
                    setOtpDispatched(true);
                    startCooldown(Number(data.cooldown_seconds || 60));
                    elements.otpInput.focus();
                    log('info', 'Unified auth OTP dispatched.', { operation: operation });
                } catch (error) {
                    setFeedback(error.message || 'Unable to send a verification code right now.', 'error');
                    log('error', 'Unified auth OTP dispatch failed.', { operation: operation, error: error.message || 'unknown_error' });
                } finally {
                    setLoading(elements.sendOtpButton, false, 'Sending...');
                    setLoading(elements.resendOtpButton, false, 'Resending...');
                }
            }

            async function verifyOtp() {
                const otp = elements.otpInput.value.trim();

                if (!otp) {
                    setFeedback('Enter the verification code first.', 'error');
                    return;
                }

                setLoading(elements.verifyOtpButton, true, 'Verifying...');
                setFeedback('Verifying your code.', 'muted');

                try {
                    const data = await postJson(endpoints.verifyOtp, Object.assign({}, otpPayload(), { otp: otp }));

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    if (data.requires_registration_fields) {
                        renderRegistrationFields(data.fields || []);
                        elements.registrationSection.classList.remove('d-none');
                    }

                    setFeedback(data.message || 'Verification successful.', 'success');
                    log('info', 'Unified auth OTP verification succeeded.', data);
                } catch (error) {
                    setFeedback(error.message || 'Verification failed.', 'error');
                    log('warn', 'Unified auth OTP verification failed.', { error: error.message || 'unknown_error' });
                } finally {
                    setLoading(elements.verifyOtpButton, false, 'Verifying...');
                }
            }

            async function completeRegistration() {
                setLoading(elements.completeRegistrationButton, true, 'Creating...');
                setFeedback('Creating your account.', 'muted');

                const formData = new FormData(elements.registrationForm || document.getElementById('unified-registration-form'));
                const fields = {};

                formData.forEach(function (value, key) {
                    const match = key.match(/^fields\[(.+)\]$/);
                    if (match) {
                        fields[match[1]] = value;
                    }
                });

                try {
                    const data = await postJson(endpoints.completeRegistration, {
                        flow_token: state.flowToken,
                        fields: fields
                    });
                    setFeedback(data.message || 'Account created successfully.', 'success');

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                } catch (error) {
                    setFeedback(error.message || 'Unable to complete registration.', 'error');
                    log('error', 'Unified auth registration completion failed.', { error: error.message || 'unknown_error' });
                } finally {
                    setLoading(elements.completeRegistrationButton, false, 'Creating...');
                }
            }

            elements.identifyButton.addEventListener('click', identify);
            elements.resetButton.addEventListener('click', resetState);
            elements.switchToOtpButton.addEventListener('click', showOtpPanel);
            elements.switchToPasswordButton.addEventListener('click', showPasswordPanel);
            elements.sendOtpButton.addEventListener('click', function () { sendOtp(endpoints.sendOtp, 'send'); });
            elements.resendOtpButton.addEventListener('click', function () { sendOtp(endpoints.resendOtp, 'resend'); });
            elements.verifyOtpButton.addEventListener('click', verifyOtp);
            elements.completeRegistrationButton.addEventListener('click', completeRegistration);

            detectCountry();
        });
    </script>
@endpush
@endsection
