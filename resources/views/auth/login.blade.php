@extends('layouts.app')

@section('title', __('Login'))

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <hgroup>
            <h1>{{ __('Login') }}</h1>
            <p>{{ __('Administrators only.') }}</p>
        </hgroup>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">
                {{ __('Email Address') }}
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email" autofocus
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <small id="email-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password">
                {{ __('Password') }}
                <input id="password" type="password" name="password"
                       required autocomplete="current-password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <small id="password-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="remember">
                <input id="remember" type="checkbox" name="remember" role="switch"
                       @checked(old('remember'))>
                {{ __('Remember Me') }}
            </label>

            <button type="submit">{{ __('Login') }}</button>
        </form>

        <small><a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a></small>
    </article>
@endsection
