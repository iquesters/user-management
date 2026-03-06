@php
    $popupLoginEnabled = (bool) ($config->popup_login ?? false);
    $googleRedirectUrl = route('google.redirect', $popupLoginEnabled ? ['popup' => 1] : []);
    $oneTapEnabled = (bool) ($config->one_tap_enabled ?? false);
    $autoSignin = (bool) ($config->auto_signin ?? false);
    $googleClientId = $config->client_id ?? null;
@endphp

<div class="d-flex align-items-center justify-content-center">
    <a href="{{ $googleRedirectUrl }}"
        class="btn btn-light btn-sm d-flex align-items-center js-google-login-btn"
        data-google-popup="{{ $popupLoginEnabled ? '1' : '0' }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20" class="me-2">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 
                30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 
                13.72 17.74 9.5 24 9.5z"></path>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 
                2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 
                7.09-17.65z"></path>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59
                l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 
                10.78l7.97-6.19z"></path>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 
                1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 
                6.19C6.51 42.62 14.62 48 24 48z"></path>
        </svg>
        Continue with Google
    </a>
</div>

@once
<script>
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.js-google-login-btn');

        if (!trigger || trigger.dataset.googlePopup !== '1') {
            return;
        }

        event.preventDefault();

        const width = 520;
        const height = 680;
        const left = window.screenX + (window.outerWidth - width) / 2;
        const top = window.screenY + (window.outerHeight - height) / 2;

        const popup = window.open(
            trigger.href,
            'googleLoginPopup',
            `popup=yes,width=${width},height=${height},left=${left},top=${top}`
        );

        if (!popup) {
            window.location.href = trigger.href;
        }
    });

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin || !event.data || !event.data.type) {
            return;
        }

        if (event.data.type === 'google-auth-success') {
            if (event.data.redirect_url) {
                window.location.href = event.data.redirect_url;
                return;
            }
            window.location.reload();
            return;
        }

        if (event.data.type === 'google-auth-failed') {
            window.location.reload();
        }
    });
</script>
@endonce

@if ($oneTapEnabled && !empty($googleClientId))
    @once
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <script>
            (function () {
                const clientId = @json($googleClientId);
                const autoSignin = @json($autoSignin);
                const oneTapEndpoint = @json(route('google.onetap'));
                const csrfToken = @json(csrf_token());
                const redirectUrl = window.location.href;

                function submitOneTapCredential(credential) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = oneTapEndpoint;
                    form.style.display = 'none';

                    const fields = {
                        _token: csrfToken,
                        credential: credential,
                        redirect_url: redirectUrl
                    };

                    Object.keys(fields).forEach(function (key) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }

                function initGoogleOneTap() {
                    if (!window.google || !window.google.accounts || !window.google.accounts.id) {
                        return false;
                    }

                    window.google.accounts.id.initialize({
                        client_id: clientId,
                        auto_select: autoSignin,
                        cancel_on_tap_outside: false,
                        callback: function (response) {
                            if (response && response.credential) {
                                submitOneTapCredential(response.credential);
                            }
                        }
                    });

                    window.google.accounts.id.prompt();
                    return true;
                }

                let attempts = 0;
                const maxAttempts = 20;
                const timer = setInterval(function () {
                    attempts++;
                    if (initGoogleOneTap() || attempts >= maxAttempts) {
                        clearInterval(timer);
                    }
                }, 250);
            })();
        </script>
    @endonce
@endif
