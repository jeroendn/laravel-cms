@extends('layouts.app')

@section('title', __('Set a new password'))

@section('content')
    <div class="card card-md mx-auto" style="max-width: 30rem;">
        <div class="card-body">
            <h1 class="card-title">{{ __('Set a new password') }}</h1>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label class="form-label" for="email">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autocomplete="email" autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">{{ __('New password') }}</label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="new-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password-confirm">{{ __('Repeat new password') }}</label>
                    <input id="password-confirm" type="password" name="password_confirmation"
                           class="form-control" required autocomplete="new-password">
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Save password') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
