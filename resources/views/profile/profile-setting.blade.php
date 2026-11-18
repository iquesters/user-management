@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? ConfProvider::from(Module::USER_INFE)->app_layout
        : ConfProvider::from(Module::USER_MGMT)->app_layout;

    $sections = ['basic', 'password', 'delete'];
    $active = in_array(request('tab'), $sections, true) ? request('tab') : 'basic';

    // Each section is a form schema (seeded by UserManagementSeeder).
    $panes = [
        'basic' => [
            'icon' => 'fas fa-fw fa-user',
            'label' => 'Basic information',
            'title' => 'Basic information',
            'text' => 'Your name and how the app looks for you.',
            'form' => 'user-profile',
            'data' => $profileData,
        ],
        'password' => [
            'icon' => 'fas fa-fw fa-lock',
            'label' => $hasPassword ? 'Password' : 'Set a password',
            'title' => $hasPassword ? 'Password' : 'Set a password',
            'text' => $hasPassword
                ? 'Use a long, random password to keep your account secure.'
                : 'You signed up without a password. Set one to also sign in with your email and password.',
            'form' => $hasPassword ? 'user-password-update' : 'user-password-set',
            'data' => [],
        ],
        'delete' => [
            'icon' => 'fas fa-fw fa-trash',
            'label' => 'Delete account',
            'title' => 'Delete account',
            'text' => 'Deleting your account permanently removes it and all of its data. This cannot be undone. Download anything you want to keep first.',
            'form' => $hasPassword ? 'user-delete-account' : 'user-delete-account-email',
            'data' => [],
            'danger' => true,
        ],
    ];
@endphp

@extends($layout)

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make(['Settings']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Update your profile information and password.'))

@section('content')
@include('usermanagement::profile.partials.tabs')

<div class="row g-4">
    <div class="col-12 col-md-4 col-lg-3">
        <div class="nav flex-md-column nav-pills gap-1" id="settingsNav" role="tablist" aria-orientation="vertical">
            @foreach ($panes as $key => $pane)
                <button class="nav-link text-start {{ ($pane['danger'] ?? false) ? 'text-danger' : '' }} {{ $active === $key ? 'active' : '' }}"
                        id="settings-{{ $key }}-tab" data-section="{{ $key }}"
                        data-bs-toggle="pill" data-bs-target="#settings-{{ $key }}" type="button" role="tab"
                        aria-controls="settings-{{ $key }}" aria-selected="{{ $active === $key ? 'true' : 'false' }}">
                    <i class="{{ $pane['icon'] }} me-1"></i>{{ $pane['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="col-12 col-md-8 col-lg-9">
        <div class="tab-content settings-panes">
            @foreach ($panes as $key => $pane)
                <div class="tab-pane fade {{ $active === $key ? 'show active' : '' }}" id="settings-{{ $key }}" role="tabpanel" aria-labelledby="settings-{{ $key }}-tab">
                    <h6 class="fw-semibold mb-1 {{ ($pane['danger'] ?? false) ? 'text-danger' : '' }}">{{ $pane['title'] }}</h6>
                    <p class="text-muted small mb-3">{{ $pane['text'] }}</p>
                    @include('userinterface::components.form', [
                        'id' => $pane['form'],
                        'form_mode' => 'edit',
                        'formData' => $pane['data'] ? json_encode($pane['data']) : '',
                    ])
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ \Iquesters\UserManagement\Support\Assets::url('css/profile.css') }}">
@endpush

@push('scripts')
<script src="{{ \Iquesters\UserManagement\Support\Assets::url('js/profile-settings.js') }}"></script>
@endpush
