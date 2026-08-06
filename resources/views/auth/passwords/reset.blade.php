@extends('layouts.app')

@section('title', __('Set a new password'))

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <h1>{{ __('Set a new password') }}</h1>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">
                {{ __('Email Address') }}
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                       required autocomplete="email" autofocus
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <small id="email-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password">
                {{ __('New password') }}
                <input id="password" type="password" name="password"
                       required autocomplete="new-password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <small id="password-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password-confirm">
                {{ __('Repeat new password') }}
                <input id="password-confirm" type="password" name="password_confirmation"
                       required autocomplete="new-password">
            </label>

            <button type="submit">{{ __('Save password') }}</button>
        </form>
    </article>
@endsection
