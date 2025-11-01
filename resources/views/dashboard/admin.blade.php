@php
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;

    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? ConfProvider::from(Module::USER_INFE)->app_layout
        : ConfProvider::from(Module::USER_MGMT)->app_layout;

    $isOrganisationNeeded = ConfProvider::from(Module::USER_MGMT)->organisation_needed;
@endphp

@extends($layout)

@section('content')
    <div>
        <!-- Welcome Message -->
        <div class="mb-4">
            <h5 class="text-muted fs-6">Welcome {{ $user->getRoleNames()->implode(', ') }}, {{ $user->name }}!</h5>
        </div>
    </div>
@endsection