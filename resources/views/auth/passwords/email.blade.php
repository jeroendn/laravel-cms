@extends('layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <hgroup>
            <h1>{{ __('Reset Password') }}</h1>
            <p>{{ __('Enter your e-mail address and you will receive a link to set a new password.') }}</p>
        </hgroup>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
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

            <button type="submit">{{ __('Send Password Reset Link') }}</button>
        </form>

        <small><a href="{{ route('login') }}">{{ __('Back to login') }}</a></small>
    </article>
@endsection
