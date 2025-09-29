@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)
@section('content')
<div class="w-100">
    hola
    {{$user}}
</div>
@endsection