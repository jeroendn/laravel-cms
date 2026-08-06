<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="container">
        <nav>
            <ul>
                <li><strong><a href="{{ route('home') }}" class="contrast">{{ config('app.name') }}</a></strong></li>
            </ul>
            <ul>
                <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                @auth
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="outline secondary">{{ __('Logout') }}</button>
                        </form>
                    </li>
                @endauth
            </ul>
        </nav>
    </header>

    <main class="container">
        @yield('content')
    </main>

    <footer class="container">
        <small>&copy; {{ date('Y') }} {{ config('app.name') }}</small>
    </footer>
</body>
</html>
