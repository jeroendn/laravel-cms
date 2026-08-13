@extends('layouts.app')

@section('title', __('Login'))

@section('content')
    <div class="card card-md mx-auto" style="max-width: 30rem;">
        <div class="card-body">
            <h1 class="card-title">{{ __('Login') }}</h1>
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label required" for="email">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autocomplete="email" autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required" for="password">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input id="remember" class="form-check-input" type="checkbox" name="remember"
                               @checked(old('remember'))>
                        <span class="form-check-label">{{ __('Remember Me') }}</span>
                    </label>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Login') }}</button>
                </div>
            </form>
        </div>

        <div class="card-footer text-center">
            <a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
        </div>
    </div>
@endsection
