<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    {{-- Tabler themes on [data-bs-theme], not on prefers-color-scheme. Before
         the stylesheet, so there is no flash of the wrong theme. --}}
    <script>
        (() => {
            const dark = window.matchMedia('(prefers-color-scheme: dark)');
            const apply = () => document.documentElement.dataset.bsTheme = dark.matches ? 'dark' : 'light';

            apply();
            dark.addEventListener('change', apply);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbar-menu" aria-controls="navbar-menu"
                        aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a href="{{ route('home') }}" class="navbar-brand">
                    {{ config('app.name') }}
                </a>

                @auth
                    <div class="navbar-nav flex-row order-md-last align-items-center">
                        <div class="nav-item ms-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">{{ __('Logout') }}</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <header class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('home') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                   role="button" aria-expanded="false">{{ __('Posts') }}</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                   role="button" aria-expanded="false">{{ __('Placeholder') }}</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                   role="button" aria-expanded="false">{{ __('Placeholder') }}</a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                    <a class="dropdown-item" href="#">{{ __('Placeholder') }}</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>

            <footer class="footer d-print-none">
                <div class="container-xl">
                    <div class="text-secondary">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
