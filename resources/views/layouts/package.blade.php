<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth</title>

    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    @if (config('usermanagement.recaptcha.enabled'))
        <script>
            window.recaptchaSiteKey = '{{ config('usermanagement.recaptcha.site_key') }}';
        </script>
        
        <!-- reCAPTCHA v3 -->
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('usermanagement.recaptcha.enabled') ? config('usermanagement.recaptcha.site_key') : 'dummy' }}"></script>
    @endif


    @stack('styles')
</head>
<body>
    <!-- Main Content -->
    <main>
        <div class="w-100 d-flex align-items-center justify-content-center px-4" style="min-height: 100vh;">
            <div class="row justify-content-md-center w-100">
                <div class="col-md-7 col-lg-4 d-flex flex-column justify-content-center align-items-center card shadow-lg rounded-4 p-4">
                    
                    <!-- Logo (now responsive) -->
                    <img src="{{ Iquesters\UserManagement\UserManagementServiceProvider::getLogoUrl() }}" 
                         class="img-fluid mb-4" 
                         alt="Logo" 
                         style="max-width: 200px; width: 100%;">

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="alert alert-info m-0 mt-3 p-2 w-100" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success m-0 mt-3 p-2 w-100" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger m-0 mt-3 p-2 w-100" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all toggle buttons
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Find the associated password input (previous sibling)
                    const passwordInput = this.parentNode.querySelector('input');
                    
                    if (passwordInput) {
                        // Toggle password visibility
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        
                        // Toggle icon
                        const icon = this.querySelector('i');
                        icon.classList.toggle('fa-eye-slash');
                        icon.classList.toggle('fa-eye');
                    }
                });
            });
        });
    </script>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <script src="{{ Iquesters\UserManagement\UserManagementServiceProvider::getJsUrl('js/recaptcha.js') }}"></script>

    @stack('scripts')
</body>
</html>