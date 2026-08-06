@extends('layouts.app')

@section('title', 'Wachtwoord vergeten')

@section('content')
    <article style="max-width: 30rem; margin-inline: auto;">
        <hgroup>
            <h1>Wachtwoord vergeten</h1>
            <p>Vul je e-mailadres in; je ontvangt een link om een nieuw wachtwoord in te stellen.</p>
        </hgroup>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
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

            <button type="submit">Verstuur resetlink</button>
        </form>

        <small><a href="{{ route('login') }}">Terug naar inloggen</a></small>
    </article>
@endsection
