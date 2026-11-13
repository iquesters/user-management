@extends(app('auth.layout'))

@section('content')
<div class="w-100">
    <div class="mb-4 text-muted">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @if ($errors->has('password'))
            <div class="text-danger mt-2">
                {{ $errors->first('password') }}
            </div>
            @endif
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-outline-info">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</div>
@endsection
