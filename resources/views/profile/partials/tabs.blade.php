{{-- Shared tab bar for the signed-in user's own pages. --}}
@include('userinterface::layouts.common.tabs', ['tabs' => [
    ['route' => 'myprofile', 'icon' => 'far fa-fw fa-id-badge', 'label' => 'My Profile'],
    ['route' => 'settings', 'icon' => 'fas fa-fw fa-sliders', 'label' => 'Settings'],
]])
