@extends('layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <div class="card card-md mx-auto" style="max-width: 30rem;">
        <div class="card-body">
            <h1 class="card-title">{{ __('Reset Password') }}</h1>
            <p class="text-secondary">
                {{ __('Enter your e-mail address and you will receive a link to set a new password.') }}
            </p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autocomplete="email" autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        {{ __('Send Password Reset Link') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card-footer text-center">
            <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
        </div>
    </div>
@endsection
