@php
    use Iquesters\Foundation\Enums\Module;
    use Iquesters\Foundation\Support\ConfProvider;

    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? ConfProvider::from(Module::USER_INFE)->app_layout
        : ConfProvider::from(Module::USER_MGMT)->app_layout;
@endphp

@extends($layout)

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([$user->name, 'My Profile']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Your profile'))

@section('content')
@include('usermanagement::profile.partials.tabs')

{{-- The whole page is the "user-profile" form schema in view mode: picture (with
     Change / Remove forms in a modal), details, and the Edit link to Settings. --}}
<div class="profile-details">
    @include('userinterface::components.form', [
        'id' => 'user-profile',
        'form_mode' => 'view',
        'formData' => json_encode($profileData),
    ])
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ \Iquesters\UserManagement\Support\Assets::url('css/profile.css') }}">
@endpush
