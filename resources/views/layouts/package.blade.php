<!DOCTYPE html>
<html lang="en">
<head>
    @include('usermanagement::layouts.common.head')
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

    @include('usermanagement::layouts.common.script')
</body>
</html>