<!DOCTYPE html>
<html>
<head>
    <title>Auth</title>
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
    <!-- reCAPTCHA v3 -->
    {{-- <script src="https://www.google.com/recaptcha/api.js?render={{ \Iquesters\UserManagement\Services\RecaptchaService::getSiteKey() }}"></script> --}}
<!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('usermanagement.recaptcha.site_key') }}"></script>

    @stack('styles')
</head>
<body>
    <div class="container-fluid" style="min-height: calc(100vh - 65px);">
        <!-- Main Content -->
        <main>
            <div class="w-100 d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 64px);">
                <div class="row justify-content-md-center w-100">
                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
                        <img src="{{ Iquesters\UserManagement\UserManagementServiceProvider::getLogoUrl() }}" class="img-fluid mb-3" alt="Logo" style="width: 275px;">
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
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>