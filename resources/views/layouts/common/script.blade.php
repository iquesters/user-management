<script>
    // Event delegation so toggle-password buttons added after page load
    // (e.g. schema-rendered forms, which insert their fields asynchronously)
    // still work, not just ones present at DOMContentLoaded.
    document.addEventListener('click', function(event) {
        const button = event.target.closest('.toggle-password');
        if (!button) {
            return;
        }

        const passwordInput = button.parentNode.querySelector('input');

        if (passwordInput) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye-slash');
                icon.classList.toggle('fa-eye');
            }
        }
    });
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

<script src="{{ Iquesters\UserManagement\UserManagementServiceProvider::getJsUrl('js/recaptcha.js') }}"></script>

@stack('scripts')