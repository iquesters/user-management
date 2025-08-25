@extends(config('usermanagement.layout_app'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs mb-2 nav-tabs-sticky-top" id="organisationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link d-flex d-lg-block flex-column flex-lg-row align-items-center px-2 px-md-3 @if(request()->routeIs('roles.show')) active @endif"
                    href="{{ route('roles.show', [$role->id]) }}"
                    id="overview-tab">
                    <i class="far fa-list-alt me-1"></i> Overview
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link d-flex d-lg-block flex-column flex-lg-row align-items-center px-2 px-md-3 @if(request()->routeIs('roles.permissions')) active @endif"
                href="{{ route('roles.permissions', [$role->id]) }}"
                id="permissions-tab">
                    <i class="fas fa-shield-alt me-2"></i> Permissions
                </a>
            </li>
        </ul>
        @yield('role-content')
    </div>
</div>
@endsection
    