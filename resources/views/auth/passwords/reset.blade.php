@extends('layouts.app')

@section('title', 'Nieuw wachtwoord instellen')

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <h1>Nieuw wachtwoord instellen</h1>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">
                E-mailadres
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                       required autocomplete="email" autofocus
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <small id="email-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password">
                Nieuw wachtwoord
                <input id="password" type="password" name="password"
                       required autocomplete="new-password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <small id="password-error">{{ $message }}</small>
                @enderror
            </label>

            <label for="password-confirm">
                Herhaal nieuw wachtwoord
                <input id="password-confirm" type="password" name="password_confirmation"
                       required autocomplete="new-password">
            </label>

            <button type="submit">Wachtwoord opslaan</button>
        </form>
    </article>
@endsection
