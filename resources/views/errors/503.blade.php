<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="30">
    <title>{{ __('Maintenance') }} — {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --background-color: #f9fafb;
            --color: #1f2937;
            --muted-color: rgba(31, 41, 55, 0.75);
            --primary: #0f766e;
        }

        @media only screen and (prefers-color-scheme: dark) {
            :root {
                --background-color: #111827;
                --color: #e5e7eb;
                --muted-color: rgba(229, 231, 235, 0.75);
                --primary: #14b8a6;
            }
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            background-color: var(--background-color);
            color: var(--color);
            font-family: system-ui, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell,
                Helvetica, Arial, "Helvetica Neue", sans-serif;
            line-height: 1.5;
            text-align: center;
        }

        main {
            max-width: 34rem;
        }

        h1 {
            margin: 0 0 0.5rem;
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            font-weight: 700;
        }

        p {
            margin: 0;
            font-size: 1.125rem;
        }

        .note {
            margin-top: 1.5rem;
            color: var(--muted-color);
            font-size: 0.875rem;
        }

        .dots span {
            color: var(--primary);
            animation: blink 1.4s infinite both;
        }

        .dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes blink {
            0%, 80%, 100% { opacity: 0; }
            40% { opacity: 1; }
        }

        /* Respect a reduced-motion preference: show the dots, don't blink. */
        @media (prefers-reduced-motion: reduce) {
            .dots span {
                animation: none;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ config('app.name') }}</h1>
        <p>
            {{ __('Back online shortly') }}<span class="dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>
        </p>
        <p class="note">
            {{ __('The website is briefly unavailable while we perform maintenance. This page refreshes automatically.') }}
        </p>
    </main>
</body>
</html>
