@extends(app('auth.layout'))

@section('auth-card-class', 'col-md-7 col-lg-4')

@section('content')
<div class="w-100">
    <h1 class="h6 mb-2">{{ __('Before you continue') }}</h1>
    <p class="small text-muted mb-3">
        {{ __('Please read and agree to the Terms and conditions and the Privacy policy. We ask again only when they change.') }}
    </p>

    @include('userinterface::components.form', ['id' => $formId])

    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-muted">{{ __('Sign out') }}</button>
    </form>
</div>
@endsection
