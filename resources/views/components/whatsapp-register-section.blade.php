@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $config = ConfProvider::from(Module::USER_MGMT);
    $socialLogin = $config->social_login;
    $socialEnabled = (bool) ($socialLogin->enabled ?? false);
    $whatsAppLogin = $config->whatsapp_login;
    $whatsAppEnabled = $socialEnabled && (bool) ($whatsAppLogin->enabled ?? false);
@endphp

@if ($whatsAppEnabled)
    <div class="d-grid">
        <button type="button" class="btn btn-sm btn-success" id="whatsapp-register-trigger">
            Register with WhatsApp
        </button>
    </div>

    @push('scripts')
        <script>
            // @todo Move WhatsApp registration behavior into dedicated auth assets once the shared OTP UI is extracted from Blade.
            document.addEventListener('DOMContentLoaded', function () {
                const trigger = document.getElementById('whatsapp-register-trigger');
                const classicSection = document.getElementById('register-classic-section');
                const whatsappSection = document.getElementById('register-whatsapp-panel');
                const alternateSection = document.getElementById('register-alternate-auth');
                const backButton = document.getElementById('whatsapp-register-back');

                if (!trigger || !classicSection || !whatsappSection || !alternateSection || !backButton) {
                    return;
                }

                trigger.addEventListener('click', function () {
                    classicSection.classList.add('d-none');
                    alternateSection.classList.add('d-none');
                    whatsappSection.classList.remove('d-none');
                });

                backButton.addEventListener('click', function () {
                    whatsappSection.classList.add('d-none');
                    classicSection.classList.remove('d-none');
                    alternateSection.classList.remove('d-none');
                });
            });
        </script>
    @endpush
@endif
