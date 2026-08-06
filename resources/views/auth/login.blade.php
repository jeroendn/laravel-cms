@extends('layouts.app')

@section('title', 'Inloggen')

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <hgroup>
            <h1>Inloggen</h1>
            <p>Alleen voor beheerders van {{ config('app.name') }}.</p>
        </hgroup>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">
                E-mailadres
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email" autofocus
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <small id="email-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password">
                Wachtwoord
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
                Ingelogd blijven
            </label>

            <button type="submit">Inloggen</button>
        </form>

        <small><a href="{{ route('password.request') }}">Wachtwoord vergeten?</a></small>
    </article>
@endsection
