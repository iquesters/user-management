
@php
    use Iquesters\Foundation\Support\ConfProvider;
    use Iquesters\Foundation\Enums\Module;

    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? ConfProvider::from(Module::USER_INFE)->app_layout
        : ConfProvider::from(Module::USER_MGMT)->app_layout;
@endphp

@extends($layout)


<?php
// $user_logo = isset($user?->meta()?->logo) ? $user?->meta()?->logo : null;
$user_logo = isset($userMetas['profile_picture'])
    ? $userMetas['profile_picture_path'] . $userMetas['profile_picture']
    : null;

$options = (object)array(
    'form' => (object)array(
        'id' => 'user-meta-logo',
        'action' => route('profile.update', $user->slug)
    ),
    'img' => (object)array(
        'id' => 'user-logo',
        'src' => $user_logo,
        'alt' => $user->name,
        'width' => '100%',
        'class' => 'rounded',
        'container_class' => 'img-thumbnail rounded',
        'aspect_ratio' => '1/1'
    ),
);

$first_name = explode(' ', trim($user->name))[0];

$user_sessions = isset($sessions) ? $sessions : null;

?>

@section('title')
{{ $user->name }} - Profile
@endsection

@section('content')
<div class="card border-0">
    <div class="card-header bg-white border-bottom px-0 sticky-alert">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 text-truncate" style="width:88%;">
                <i class="far fa-fw fa-id-badge"></i>
                <h5 class="mb-0 mt-1 text-truncate">{{ $user->name }}'s Profile</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- <!-- <a class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" href="{{ route('user.settings') }}"><i class="fas fa-fw fa-sliders"></i><span class="d-none d-md-block">Settings</span></a> --> --}}
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="row g-2 justify-content-center">
            <div class="col-6 col-sm-6 col-md-3">
                @include('usermanagement::utils.image-changer', ['options' => $options])
            </div>
            <div class="col-12 col-sm-10 col-md-9">
                <h2 class="mb-2 fw-bold text-truncate">{{ $user->name }}</h2>
                <!-- <p class="mb-0 d-flex justify-content-center align-items-center small">
                            <span class="text-truncate">UID: {{!! $user->uid !!}}</span>
                            <button class="btn btn-sm btn-link" title="Copy OID" onclick="copyToClipboard('{{ $user->uid }}', this)"><i class="far fa-fw fa-copy"></i></button>
                        </p> -->
                <!-- email -->
                <div class="mb-2">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="far fa-fw fa-envelope"></i>
                        <span>
                            @isset($user->email)
                            {{ $user->email }}
                            @else
                            <span>not provided yet</span>
                            @endif
                        </span>
                    </h5>
                    <span class="small">
                        <small>&nbsp;&nbsp;
                            @isset($user->email_verified_at)
                            <i class="fas fa-fw fa-circle-check text-success"></i>
                            <span>verified at: {{ $user->email_verified_at }}</span>
                            @else
                            <i class="fas fa-fw fa-circle-exclamation text-warning"></i>
                            <span>not verified yet</span>
                            @endif
                        </small>
                    </span>
                </div>
                <!-- phone -->
                <div class="mb-2">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-fw fa-mobile-screen"></i>
                        <span>
                            @isset($user->phone)
                            {{ $user->phone }}
                            @else
                            <span>not provided yet</span>
                            @endif
                        </span>
                    </h5>
                    <span class="small">
                        <small>&nbsp;&nbsp;
                            @isset($user->phone_verified_at)
                            <i class="fas fa-fw fa-circle-check text-success"></i>
                            <span>verified at: {{ $user->email_verified_at }}</span>
                            @else
                            <i class="fas fa-fw fa-circle-exclamation text-warning"></i>
                            <span>not verified yet</span>
                            @endif
                        </small>
                    </span>
                </div>

                @php
                $roles = $user->getRoleNames();
                @endphp
                <p class="mb-1 d-flex align-items-center small">Member since: {{ date_format($user->created_at,"d M, Y") }}</p>
                <p class="mb-1 d-flex align-items-center small text-truncate">Role(s): {{ $roles->isNotEmpty() && $roles->count() > 0 ? implode(',', $roles->toArray()) : 'unknown' }}</p>
                <p class="mb-2 d-flex align-items-center small">Status: @include('usermanagement::utils.status',['status'=>$user->status])</p>
            </div>
        </div>
    </div>
</div>
<div class="d-none card border-0 border-bottom mb-3">
    <div class="card-header bg-white border-bottom px-0 sticky-alert">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2" style="width:88%;">
                <i class="fas fa-fw fa-user-shield"></i>
                <h5 class="mb-0 mt-1">{{ isset($first_name) ? $first_name : '' }}'s Session</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- <!-- <a class="btn btn-sm btn-outline-secondary d-flex align-items-center" href="{{ route('user.settings') }}"><i class="fas fa-fw fa-sliders"></i><span class="d-none d-md-block">&nbsp;Settings</span></a> --> --}}
            </div>
        </div>
    </div>
    <div class="card-body px-0">
        <div class="row row-cols-1 g-2">
            <div class="col offset-md-2 col-md-8">
                @include('usermanagement::utils.session', ['options' => $user_sessions])

                <div class="card border-0 mb-3">
                    <div class="card-body">
                        <div class="row row-cols-1 g-2">
                            <div class="col-4 d-flex align-items-start justify-content-end">
                                <i class="fas fa-fw fa-4x fa-laptop"></i>
                            </div>
                            <div class="col-8">
                                <p class="card-text mb-1">Chrome on Windows</p>
                                <p class="card-text mb-1"><span id="--user-region--"><span class="placeholder-wave placeholder bg-light col-12"></span></span></p>
                                <p class="card-text mb-1"><small class="text-body-secondary">Timezone: <span id="--user-tz--"><span class="placeholder-wave placeholder bg-light col-12"></span></span></small></p>
                                <p class="card-text mb-1"><small class="text-body-secondary">IP: <span id="--user-ip--"><span class="placeholder-wave placeholder bg-light col-12"></span></span></small></p>
                                <p class="card-text mb-1 small"><small class="text-body-secondary">Last accessed at: {{date('d M, Y, h:m:s A')}}</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="d-none card border-0 border-bottom mb-3">
    <div class="card-header bg-white border-bottom px-0 sticky-alert">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2" style="width:88%;">
                <i class="fas fa-fw fa-mobile-screen-button"></i>
                <h5 class="mb-0 mt-1">{{ isset($first_name) ? $first_name : '' }}'s Devices</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- <!-- <a class="btn btn-sm btn-outline-secondary d-flex align-items-center" href="{{ route('user.settings') }}"><i class="fas fa-fw fa-sliders"></i><span class="d-none d-md-block">&nbsp;Settings</span></a> --> --}}
            </div>
        </div>
    </div>
    <div class="card-body px-0">
        <div class="row row-cols-1 g-2">
            @forelse($user_sessions as $user_session)
            <?php $is_mobile = str_contains($user_session->user_agent, 'Mobi') ?>

            <div class="col offset-md-2 col-md-8">
                <div class="card border-0 mb-3">
                    <div class="card-body">
                        <div class="row row-cols-1 g-2">
                            <div class="col-4 d-flex align-items-start justify-content-end">
                                @if($is_mobile)
                                <i class="fas fa-fw fa-4x fa-mobile-screen-button"></i>
                                @else
                                <i class="fas fa-fw fa-4x fa-laptop"></i>
                                @endif
                            </div>
                            <div class="col-8">
                                <p class="card-text mb-1">Chrome on Windows</p>
                                <p class="card-text mb-1"><span id="">West Bengal, India</span></p>
                                <p class="card-text mb-1"><small class="text-body-secondary">IP: <span>{{$user_session->ip_address}}</span></small></p>
                                <p class="card-text mb-1 small"><small class="text-body-secondary">Last accessed at: {{date('d M, Y, h:m:s A',$user_session->last_activity)}}</small></p>
                                <p class="card-text mb-1 small"><small class="text-body-secondary">{{$user_session->user_agent}}</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col">
                <p>No other devices found with active session</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- from dolibarr -->
<!-- Administrator: Yes
Type: Internal
Status: Enabled

Session
IP address: 45.117.206.98
Authentication mode: dolibarr
Connected since: 02/09/2024 06:15 PM
Previous login: 31/08/2024 06:50 PM
Current theme: eldy
Current menu manager: eldy
Current language: en_IN
ClientTZ: +5 (Asia/Calcutta)
Browser: chrome 127.0.0.0 (Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36)
Layout: classic
Screen: 1600 x 689 -->

@php
$options = (object)(['selectable'=>true]);
@endphp
{{-- @include('usermanagement::media.parts.library', compact('options')) --}}
@include('usermanagement::media.parts.dropbox')
@include('usermanagement::media.parts.remove-image')
@endsection

@push('rs-content-prepend')
@if(Auth::user()?->hasRole('organizer'))
@include('global.rsb.rsb-organization-select')
@include('global.rsb.rsb-event-select')
@endif
@endpush