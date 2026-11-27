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
    // Phone-tab identify only leads somewhere if WhatsApp OTP delivery is
    // actually configured (OtpService::isChannelEnabled gates send/resend on
    // this same flag) — without it, a phone-identified user hits a dead end
    // ("WhatsApp registration is currently unavailable"), so don't offer the
    // tab at all rather than let them walk into that.
    $whatsappLoginEnabled = (bool) ($authConfig->whatsapp_login->enabled ?? false);
    // Identify is the unified flow's entry point — the one a bot would hit
    // to enumerate identifiers or force OTP sends — so it's gated the same
    // way LoginRequest gates the password path. The 'unified_identify'
    // action name here must match IdentifyAuthIdentifierRequest's
    // RecaptchaRule exactly, or Google's action-mismatch check fails it.
    $recaptchaEnabled = $authConfig->recaptcha->enabled ?? false;
@endphp

<div class="w-100 row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                {{-- The step's title and line change as the person moves on (Welcome → Check your email → …). --}}
                <div class="mb-4 text-center unified-step-head">
                    <h4 class="mb-1 fw-semibold" id="unified-step-title">Welcome</h4>
                    <p class="text-muted mb-0" id="unified-step-subtitle">
                        @if($whatsappLoginEnabled)
                            Sign in or create your account with your email or phone. No password needed.
                        @else
                            Sign in or create your account with your email. No password needed.
                        @endif
                    </p>
                </div>

                {{-- Who is signing in: the email (or phone) entered, with Change. --}}
                <div id="unified-identity" class="d-none unified-identity mb-3">
                    <span class="d-inline-flex align-items-center gap-2 text-truncate">
                        <i class="fas fa-circle-user text-primary"></i>
                        <span id="unified-identity-text" class="fw-medium text-truncate"></span>
                    </span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="unified-reset-button">Change</button>
                </div>

                {{-- Intentional inline unified-auth state machine for the package login screen; @todo move this temporary auth UI behavior into dedicated assets once the shared auth module is extracted. --}}
                {{-- Only one of identify-wrap / password-section / otp-section / registration-section
                     is visible at a time — each step replaces the previous one in place, rather than
                     stacking below it. Feedback text and the reset button sit outside all of them so
                     they stay visible across every step. --}}
                <div class="d-grid gap-3" id="unified-identify-wrap">
                    @if($whatsappLoginEnabled)
                        <ul class="nav nav-tabs" id="unified-identify-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="unified-tab-email-btn" data-bs-toggle="tab" data-bs-target="#unified-tab-email" type="button" role="tab" aria-controls="unified-tab-email" aria-selected="true">Email</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="unified-tab-phone-btn" data-bs-toggle="tab" data-bs-target="#unified-tab-phone" type="button" role="tab" aria-controls="unified-tab-phone" aria-selected="false">Phone</button>
                            </li>
                        </ul>
                    @endif
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="unified-tab-email" role="tabpanel" aria-labelledby="unified-tab-email-btn">
                            @include('userinterface::components.form', ['id' => 'unified-identify-email', 'recaptchaAction' => $recaptchaEnabled ? 'unified_identify' : null])
                        </div>
                        @if($whatsappLoginEnabled)
                            <div class="tab-pane fade" id="unified-tab-phone" role="tabpanel" aria-labelledby="unified-tab-phone-btn">
                                @include('userinterface::components.form', ['id' => 'unified-identify-phone', 'recaptchaAction' => $recaptchaEnabled ? 'unified_identify' : null])
                            </div>
                        @endif
                    </div>
                </div>

                {{-- The code (the default way): sent by itself after the email. --}}
                <div id="unified-otp-section" class="d-none">
                    @include('userinterface::components.form', ['id' => 'unified-verify-otp'])

                    <div class="text-center small mt-3 d-grid gap-2">
                        <div>
                            <span class="text-muted">Didn't get it?</span>
                            <button type="button" class="btn btn-link btn-sm p-0 align-baseline text-decoration-none" id="unified-send-otp-button">Send the code</button>
                            <button type="button" class="btn btn-link btn-sm p-0 align-baseline text-decoration-none d-none" id="unified-resend-otp-button">Resend the code</button>
                            <span class="text-muted">· check Spam too</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none d-none" id="unified-switch-to-password"><i class="fas fa-key me-1"></i>Use your password instead</button>
                        </div>
                    </div>
                </div>

                {{-- The password (the other way, for accounts that have one). --}}
                <div id="unified-password-section" class="d-none">
                    @include('userinterface::components.form', ['id' => 'unified-login-with-password', 'recaptchaAction' => $recaptchaEnabled ? 'login' : null])
                    <div class="text-center small mt-3">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none d-none" id="unified-switch-to-otp"><i class="fas fa-envelope me-1"></i>Email me a code instead</button>
                    </div>
                </div>

                {{-- A new account: the few details the app asks for. --}}
                <div id="unified-registration-section" class="d-none">
                    <form id="unified-registration-form" class="d-grid gap-3">
                        <div id="unified-registration-fields" class="d-grid gap-3"></div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-primary" id="unified-complete-registration-button">Create my account</button>
                        </div>
                    </form>
                </div>

                <div id="unified-feedback" class="small text-muted mt-3 text-center" aria-live="polite"></div>
            </div>
        </div>

        @if ($hasSocialLogin)
            <div class="mt-4">
                @include('usermanagement::components.social-login-section')
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    /* Unified sign-in: one calm column, large touch targets, a big centred code box. */
    .unified-step-head h4 { font-size: 1.5rem; }
    .unified-identity { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .6rem .9rem; border-radius: 999px; background: var(--bs-tertiary-bg); }
    #unified-otp-section input[name="otp"] { font-size: 1.75rem; letter-spacing: .55em; text-align: center;
        font-family: Consolas, Menlo, monospace; padding-left: .55em; }
    #unified-otp-section input[name="otp"]::placeholder { letter-spacing: normal; font-size: 1rem; font-family: inherit; }
</style>
@endpush

@push('scripts')
    <script>
        // @todo Move the temporary unified auth JavaScript into dedicated auth assets when the package auth UI module is extracted.
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = @json(csrf_token());
            const whatsappLoginEnabled = @json($whatsappLoginEnabled);
            // 'identify' and 'verifyOtp' aren't listed here — both forms now
            // carry their own endpoint from the schema (unified-identify-*/
            // unified-verify-otp), not this object.
            const endpoints = {
                country: @json(route('auth.unified.country')),
                sendOtp: @json(route('auth.unified.otp.send')),
                resendOtp: @json(route('auth.unified.otp.resend')),
                completeRegistration: @json(route('auth.unified.register.complete'))
            };

            const elements = {
                identifyWrap: document.getElementById('unified-identify-wrap'),
                feedback: document.getElementById('unified-feedback'),
                resetButton: document.getElementById('unified-reset-button'),
                passwordSection: document.getElementById('unified-password-section'),
                otpSection: document.getElementById('unified-otp-section'),
                sendOtpButton: document.getElementById('unified-send-otp-button'),
                resendOtpButton: document.getElementById('unified-resend-otp-button'),
                switchToOtpButton: document.getElementById('unified-switch-to-otp'),
                switchToPasswordButton: document.getElementById('unified-switch-to-password'),
                registrationSection: document.getElementById('unified-registration-section'),
                registrationForm: document.getElementById('unified-registration-form'),
                registrationFields: document.getElementById('unified-registration-fields'),
                completeRegistrationButton: document.getElementById('unified-complete-registration-button'),
                identity: document.getElementById('unified-identity'),
                identityText: document.getElementById('unified-identity-text'),
                stepTitle: document.getElementById('unified-step-title'),
                stepSubtitle: document.getElementById('unified-step-subtitle')
            };
            const firstTitle = elements.stepTitle.textContent;
            const firstSubtitle = elements.stepSubtitle.textContent.trim();

            /** The step's title and line (Welcome → Check your email → Enter your password → Almost done). */
            function setStep(title, subtitle) {
                elements.stepTitle.textContent = title;
                elements.stepSubtitle.textContent = subtitle || '';
            }

            // The identifier input now comes from whichever schema-rendered
            // tab form (#unified-identify-email / #unified-identify-phone)
            // is currently active — resolved by [name=...] on demand instead
            // of cached, since each form renders asynchronously after its
            // own schema fetch. country_dial_code is a real SELECT rendered
            // inside the phone tab's own schema form, so it no longer needs
            // external syncing.
            function getActiveIdentifyFormId() {
                const activePane = document.querySelector('#unified-identify-wrap .tab-pane.active');
                const form = activePane && activePane.querySelector('form[id^="unified-identify-"]');
                return form ? form.id : null;
            }

            function getIdentifierInput(formId) {
                const targetFormId = formId || getActiveIdentifyFormId();
                return targetFormId
                    ? document.querySelector('#' + targetFormId + ' [name="identifier"]')
                    : null;
            }

            // The verify-OTP card is its own schema form (#unified-verify-otp),
            // separate from the identify tabs — resolved the same on-demand
            // way as the identifier inputs.
            function getVerifyOtpInput() {
                return document.querySelector('#unified-verify-otp [name="otp"]');
            }

            // Generic setter for a field inside a schema-rendered form that
            // may not exist yet (schema fetch/render is async) — retries
            // briefly instead of giving up. Used for the phone tab's
            // country_dial_code SELECT (default from IP lookup) and the
            // verify-OTP form's hidden flow/identifier fields (populated
            // once the identify step resolves).
            function setSchemaFieldValue(formId, fieldName, value, attemptsRemaining) {
                const input = document.querySelector('#' + formId + ' [name="' + fieldName + '"]');
                if (input) {
                    input.value = value;
                    return;
                }

                if ((attemptsRemaining || 0) > 0) {
                    window.setTimeout(function () {
                        setSchemaFieldValue(formId, fieldName, value, attemptsRemaining - 1);
                    }, 200);
                }
            }

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
                elements.resendOtpButton.classList.toggle('d-none', !isDispatched);
            }

            function resetPanels() {
                elements.passwordSection.classList.add('d-none');
                elements.otpSection.classList.add('d-none');
                elements.registrationSection.classList.add('d-none');
                elements.switchToOtpButton.classList.add('d-none');
                elements.switchToPasswordButton.classList.add('d-none');
                elements.registrationFields.innerHTML = '';

                const otpInput = getVerifyOtpInput();
                if (otpInput) {
                    otpInput.value = '';
                }

                state.otpSent = false;
                setOtpDispatched(false);
            }

            function showPasswordPanel() {
                elements.identifyWrap.classList.add('d-none');
                elements.passwordSection.classList.remove('d-none');
                elements.otpSection.classList.add('d-none');
                setStep('Enter your password', 'Or get a code by email instead.');
                setFeedback('', 'muted');
                window.setTimeout(function () {
                    const password = document.querySelector('#unified-login-with-password input[type="password"]');
                    if (password) password.focus();
                }, 50);
            }

            function showOtpPanel() {
                elements.identifyWrap.classList.add('d-none');
                elements.otpSection.classList.remove('d-none');
                setOtpDispatched(state.otpSent);
                elements.passwordSection.classList.add('d-none');
                const input = getVerifyOtpInput();
                const digits = (input && input.maxLength > 0) ? input.maxLength : 6;
                setStep(
                    state.deliveryChannel === 'whatsapp' ? 'Check WhatsApp' : 'Check your email',
                    'We sent a ' + digits + '-digit code to ' + (state.identifier || 'you') + '. Enter it below to ' + (state.status === 'existing' ? 'sign in.' : 'create your account.')
                );

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
                elements.identifyWrap.classList.remove('d-none');
                setFeedback('', 'muted');
                elements.identity.classList.add('d-none');
                setStep(firstTitle, firstSubtitle);

                ['unified-identify-email', 'unified-identify-phone'].forEach(function (formId) {
                    const input = getIdentifierInput(formId);
                    if (input) {
                        input.disabled = false;
                        input.value = '';
                    }
                });

                const activeInput = getIdentifierInput();
                if (activeInput) {
                    activeInput.focus();
                }

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
                        elements.resendOtpButton.textContent = 'Resend the code';
                        return;
                    }

                    elements.resendOtpButton.textContent = 'Resend in ' + state.cooldownRemaining + 's';
                }, 1000);

                elements.resendOtpButton.textContent = 'Resend in ' + state.cooldownRemaining + 's';
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
                if (!whatsappLoginEnabled) {
                    // No phone tab rendered in this case — nothing to set.
                    return;
                }

                try {
                    const response = await fetch(endpoints.country, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();

                    if (data && data.dial_code) {
                        setSchemaFieldValue('unified-identify-phone', 'country_dial_code', data.dial_code, 15);
                        log('info', 'Loaded default country dial code.', data);
                    }
                } catch (error) {
                    log('warn', 'Unable to load default country dial code.', {});
                }
            }

            // Submission itself is now handled by the schema engine (each
            // tab's own form POSTing to /auth/identify, with its own loading
            // state and inline validation errors) — this only reacts to the
            // *result*, via the lab-form:submitted event, since that response
            // is flow state (existing vs new, available login methods) the
            // engine has no way to act on by itself.
            function handleIdentifySubmitted(response, formId) {
                const data = (response && response.data) || response || {};

                if (!data.flow_token) {
                    // A non-2xx/parse-failure case reaches here too (the
                    // engine already rendered its own inline error) — nothing
                    // useful to branch on.
                    return;
                }

                resetPanels();

                state.identifierType = data.identifier_type;
                state.flowToken = data.flow_token;
                state.identifier = data.normalized_identifier;
                state.deliveryChannel = data.delivery_channel;
                state.status = data.status;
                state.hasPasswordOption = (data.available_login_methods || []).includes('password') && Boolean(data.password_login_email);

                // The verify-OTP form's flow_token/identifier_type/identifier/
                // delivery_channel are hidden fields — VerifyIdentifierOtpRequest
                // requires all four alongside the otp digits, but only the
                // identify step's response tells us their values.
                setSchemaFieldValue('unified-verify-otp', 'flow_token', state.flowToken, 20);
                setSchemaFieldValue('unified-verify-otp', 'identifier_type', state.identifierType, 20);
                setSchemaFieldValue('unified-verify-otp', 'identifier', state.identifier, 20);
                setSchemaFieldValue('unified-verify-otp', 'delivery_channel', state.deliveryChannel, 20);

                // Who is signing in, with Change.
                elements.identityText.textContent = state.identifier || '';
                elements.identity.classList.remove('d-none');
                const identifierInput = getIdentifierInput(formId);
                if (identifierInput) {
                    identifierInput.disabled = true;
                }

                // The code first (it works for everyone, new or returning); a password only as the
                // other way, for accounts that have one.
                if (state.hasPasswordOption) {
                    setSchemaFieldValue('unified-login-with-password', 'email', data.password_login_email, 20);
                    elements.switchToOtpButton.classList.remove('d-none');
                    elements.switchToPasswordButton.classList.remove('d-none');
                }
                showOtpPanel();
                log('info', 'Unified auth identifier resolved.', data);
            }

            async function sendOtp(url, operation) {
                if (!state.identifierType || !state.identifier || !state.deliveryChannel) {
                    setFeedback('Start with your identifier first.', 'error');
                    return;
                }

                setLoading(operation === 'send' ? elements.sendOtpButton : elements.resendOtpButton, true, operation === 'send' ? 'Sending...' : 'Resending...');
                setFeedback('Sending your code…', 'muted');

                try {
                    const data = await postJson(url, otpPayload());
                    setFeedback('Code sent. It can take a minute to arrive.', 'success');
                    state.otpSent = true;
                    setOtpDispatched(true);
                    startCooldown(Number(data.cooldown_seconds || 60));
                    const otpInput = getVerifyOtpInput();
                    if (otpInput) {
                        otpInput.focus();
                    }
                    log('info', 'Unified auth OTP dispatched.', { operation: operation });
                } catch (error) {
                    setFeedback(error.message || 'Unable to send a verification code right now.', 'error');
                    log('error', 'Unified auth OTP dispatch failed.', { operation: operation, error: error.message || 'unknown_error' });
                } finally {
                    setLoading(elements.sendOtpButton, false, 'Sending...');
                    setLoading(elements.resendOtpButton, false, 'Resending...');
                }
            }

            // Submission itself is now handled by the schema engine (the
            // #unified-verify-otp form's own POST to /auth/unified/otp/verify,
            // with its own loading state and inline validation errors) — this
            // only reacts to the *result*. A redirect_url in the response
            // (existing-user login) is already followed automatically by the
            // engine itself; this only needs to handle the non-redirect
            // "verified, now complete registration" branch.
            function handleVerifyOtpSubmitted(response) {
                const data = (response && response.data) || response || {};

                if (data.requires_registration_fields) {
                    renderRegistrationFields(data.fields || []);
                    elements.otpSection.classList.add('d-none');
                    elements.registrationSection.classList.remove('d-none');
                    setStep('Almost done', 'A few details to finish creating your account.');
                    setFeedback('', 'muted');
                    return;
                }

                setFeedback(data.message || 'Verification successful.', 'success');
                log('info', 'Unified auth OTP verification succeeded.', data);
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
                    // The Terms / Privacy agreement was given on the first step.
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

            elements.resetButton.addEventListener('click', resetState);
            elements.switchToOtpButton.addEventListener('click', showOtpPanel);
            elements.switchToPasswordButton.addEventListener('click', showPasswordPanel);
            elements.sendOtpButton.addEventListener('click', function () { sendOtp(endpoints.sendOtp, 'send'); });
            elements.resendOtpButton.addEventListener('click', function () { sendOtp(endpoints.resendOtp, 'resend'); });
            elements.completeRegistrationButton.addEventListener('click', completeRegistration);

            window.addEventListener('lab-form:submitted', function (event) {
                const formId = event.detail && event.detail.formId;
                if (formId === 'unified-identify-email' || formId === 'unified-identify-phone') {
                    // This page shows the next step itself: the form mustn't reload the page.
                    event.preventDefault();
                    handleIdentifySubmitted(event.detail.response, formId);
                } else if (formId === 'unified-verify-otp') {
                    // A redirect_url (signed in) is still followed by the form; otherwise the next step shows here.
                    event.preventDefault();
                    handleVerifyOtpSubmitted(event.detail.response);
                }
            });

            detectCountry();
        });
    </script>
@endpush
@endsection
