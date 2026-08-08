<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="robots" content="noindex">
    @include('partials.head')
</head>
<body>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <span class="navbar-brand navbar-brand-autodark fs-2">{{ config('app.name') }}</span>
            </div>

            <div class="empty">
                <div class="empty-icon">
                    <i class="fa-solid fa-person-digging fa-2xl text-primary" aria-hidden="true"></i>
                </div>
                <p class="empty-title">{{ __('This website is under construction') }}</p>
                <p class="empty-subtitle text-secondary">
                    {{ __('We are still working on it. Please come back later.') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
