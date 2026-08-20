@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $config = ConfProvider::from(Module::USER_MGMT);
    $socialLogin = $config->social_login;
    $socialEnabled = (bool) ($socialLogin->enabled ?? false);
    $whatsAppLogin = $config->whatsapp_login;
    $whatsAppEnabled = $socialEnabled && (bool) ($whatsAppLogin->enabled ?? false);
    $defaultCooldown = (int) ($whatsAppLogin->resend_cooldown_seconds ?? 60);
    $showDivider = $showDivider ?? true;
@endphp

@if ($whatsAppEnabled)
    @if ($showDivider)
        <div class="d-flex align-items-center my-3">
            <hr class="flex-grow-1">
            <span class="mx-2 text-muted">or</span>
            <hr class="flex-grow-1">
        </div>
    @endif

    <div id="whatsapp-login-trigger-wrap" class="d-grid">
        <button type="button" class="btn btn-sm btn-success" id="whatsapp-login-trigger">
            Login with WhatsApp
        </button>
    </div>

    @push('scripts')
        <script>
            // @todo Move WhatsApp OTP auth behavior into dedicated assets when auth JavaScript is extracted from Blade.
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('whatsapp-otp-form');
                if (!form) {
                    return;
                }

                const loginForm = document.getElementById('login-form');
                const passwordSection = document.getElementById('login-password-section');
                const alternateAuthOptions = document.getElementById('alternate-auth-options');
                const whatsappTriggerWrap = document.getElementById('whatsapp-login-trigger-wrap');
                const whatsappTrigger = document.getElementById('whatsapp-login-trigger');
                const whatsappPanel = document.getElementById('whatsapp-login-panel');
                const whatsappBack = document.getElementById('whatsapp-login-back');
                const phoneInput = document.getElementById('whatsapp-phone');
                const otpInput = document.getElementById('whatsapp-otp');
                const otpEntry = document.getElementById('whatsapp-otp-entry');
                const feedback = document.getElementById('whatsapp-otp-feedback');
                const sendButton = document.getElementById('whatsapp-send-otp');
                const verifyButton = document.getElementById('whatsapp-verify-otp');
                const resendButton = document.getElementById('whatsapp-resend-otp');
                const csrfToken = @json(csrf_token());
                const endpoints = {
                    send: @json(route('whatsapp.otp.send')),
                    verify: @json(route('whatsapp.otp.verify')),
                    resend: @json(route('whatsapp.otp.resend'))
                };

                const state = {
                    cooldownSeconds: @json($defaultCooldown),
                    cooldownRemaining: 0,
                    cooldownTimer: null
                };

                function showWhatsAppPanel() {
                    if (passwordSection) {
                        passwordSection.classList.add('d-none');
                    }

                    if (alternateAuthOptions) {
                        alternateAuthOptions.classList.add('d-none');
                    }

                    whatsappPanel.classList.remove('d-none');
                    phoneInput.focus();
                    log('info', 'WhatsApp login panel opened.', {});
                }

                function hideWhatsAppPanel() {
                    whatsappPanel.classList.add('d-none');

                    if (passwordSection) {
                        passwordSection.classList.remove('d-none');
                    }

                    if (alternateAuthOptions) {
                        alternateAuthOptions.classList.remove('d-none');
                    }

                    setOtpStepVisible(false);
                    setFeedback('', 'muted');
                    otpInput.value = '';
                    log('info', 'WhatsApp login panel closed.', {});
                }

                function log(level, message, context) {
                    const payload = context || {};
                    if (level === 'error') {
                        console.error('[WhatsAppOtp]', message, payload);
                        return;
                    }
                    if (level === 'warn') {
                        console.warn('[WhatsAppOtp]', message, payload);
                        return;
                    }
                    console.info('[WhatsAppOtp]', message, payload);
                }

                function setFeedback(message, tone) {
                    feedback.textContent = message || '';
                    feedback.classList.remove('text-muted', 'text-danger', 'text-success');

                    if (tone === 'error') {
                        feedback.classList.add('text-danger');
                        return;
                    }

                    if (tone === 'success') {
                        feedback.classList.add('text-success');
                        return;
                    }

                    feedback.classList.add('text-muted');
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

                function setOtpStepVisible(isVisible) {
                    otpEntry.classList.toggle('d-none', !isVisible);
                    verifyButton.classList.toggle('d-none', !isVisible);
                    resendButton.classList.toggle('d-none', !isVisible);
                }

                function startCooldown(seconds) {
                    state.cooldownRemaining = Number(seconds || state.cooldownSeconds || 0);
                    resendButton.disabled = true;

                    if (state.cooldownTimer) {
                        window.clearInterval(state.cooldownTimer);
                    }

                    state.cooldownTimer = window.setInterval(function () {
                        state.cooldownRemaining -= 1;

                        if (state.cooldownRemaining <= 0) {
                            window.clearInterval(state.cooldownTimer);
                            state.cooldownTimer = null;
                            resendButton.disabled = false;
                            resendButton.textContent = 'Resend OTP';
                            return;
                        }

                        resendButton.textContent = 'Resend OTP (' + state.cooldownRemaining + 's)';
                    }, 1000);

                    resendButton.textContent = 'Resend OTP (' + state.cooldownRemaining + 's)';
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
                        return {
                            success: false,
                            message: 'Unexpected response from the server.'
                        };
                    });

                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                }

                async function requestOtp(url, operation) {
                    const phone = phoneInput.value.trim();

                    if (!phone) {
                        setFeedback('Please enter your WhatsApp number first.', 'error');
                        return;
                    }

                    setLoading(operation === 'send' ? sendButton : resendButton, true, 'Sending...');
                    setFeedback('Checking your request and preparing verification.', 'muted');
                    log('info', 'Submitting WhatsApp OTP request.', { operation: operation });

                    try {
                        const data = await postJson(url, { phone: phone });
                        const cooldownSeconds = Number(data.cooldown_seconds || state.cooldownSeconds);

                        setOtpStepVisible(true);
                        setFeedback(data.message || 'If the number is registered, a code has been sent.', 'success');
                        startCooldown(cooldownSeconds);
                        otpInput.focus();
                        log('info', 'WhatsApp OTP request completed.', { operation: operation });
                    } catch (error) {
                        setFeedback(error.message || 'Unable to send the verification code right now.', 'error');
                        log('error', 'WhatsApp OTP request failed.', { operation: operation, error: error.message || 'unknown_error' });
                    } finally {
                        setLoading(sendButton, false, 'Sending...');
                        setLoading(resendButton, false, 'Sending...');
                    }
                }

                async function verifyOtp() {
                    const phone = phoneInput.value.trim();
                    const otp = otpInput.value.trim();

                    if (!phone || !otp) {
                        setFeedback('Enter both your WhatsApp number and the OTP.', 'error');
                        return;
                    }

                    setLoading(verifyButton, true, 'Verifying...');
                    setFeedback('Verifying your code and creating your session.', 'muted');
                    log('info', 'Submitting WhatsApp OTP verification.', {});

                    try {
                        const data = await postJson(endpoints.verify, { phone: phone, otp: otp });
                        setFeedback(data.message || 'Logged in successfully.', 'success');
                        log('info', 'WhatsApp OTP verification succeeded.', {});

                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                            return;
                        }

                        window.location.reload();
                    } catch (error) {
                        setFeedback(error.message || 'The verification code is invalid or expired.', 'error');
                        log('warn', 'WhatsApp OTP verification failed.', { error: error.message || 'unknown_error' });
                    } finally {
                        setLoading(verifyButton, false, 'Verifying...');
                    }
                }

                sendButton.addEventListener('click', function () {
                    requestOtp(endpoints.send, 'send');
                });

                resendButton.addEventListener('click', function () {
                    requestOtp(endpoints.resend, 'resend');
                });

                verifyButton.addEventListener('click', function () {
                    verifyOtp();
                });

                otpInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        verifyOtp();
                    }
                });

                whatsappTrigger.addEventListener('click', function () {
                    showWhatsAppPanel();
                });

                whatsappBack.addEventListener('click', function () {
                    hideWhatsAppPanel();
                });
            });
        </script>
    @endpush
@endif
