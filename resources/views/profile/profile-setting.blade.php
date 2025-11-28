@php
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;

    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? ConfProvider::from(Module::USER_INFE)->app_layout
        : ConfProvider::from(Module::USER_MGMT)->app_layout;
@endphp

@extends($layout)

@section('page-title', 'Profile')
@section('meta-description', 'Update your profile information and password.')

@section('content')
<div class="">
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-2 nav-tabs-sticky-top" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                <i class="fas fa-fw fa-user me-1"></i>{{ __('Basic Information') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                <i class="fas fa-fw fa-lock me-1"></i>{{ __('Update Password') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="delete-tab" data-bs-toggle="tab" data-bs-target="#delete" type="button" role="tab">
                <i class="fas fa-fw fa-trash me-1"></i>{{ __('Delete Account') }}
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content mt-2" style="max-width: 600px">
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            {{-- @include('profile.partials.update-profile-information-form') --}}
            @include('usermanagement::profile.partials.update-profile-information-form')
        </div>
        <div class="tab-pane fade" id="password" role="tabpanel">
            {{-- @include('profile.partials.update-password-form') --}}
            @include('usermanagement::profile.partials.update-password-form')
        </div>
        <div class="tab-pane fade" id="delete" role="tabpanel">
            {{-- @include('profile.partials.delete-user-form') --}}
            @include('usermanagement::profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
