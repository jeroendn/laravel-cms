@use('App\Support\Breadcrumbs')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
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

                @adminArea
                    <a href="{{ route('admin.dashboard') }}" class="navbar-brand me-auto">
                        <span class="d-none d-md-inline">{{ config('app.name') }}</span>
                        <span class="badge bg-warning text-dark ms-md-2">{{ __('Admin area') }}</span>
                    </a>
                @else
                    <a href="{{ route('home') }}" class="navbar-brand me-auto">
                        {{ config('app.name') }}
                    </a>
                @endadminArea
            </div>
        </header>

        <header class="navbar-expand-md d-print-none">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar">
                    <div class="container-xl">
                        <ul class="navbar-nav w-100">
                            @adminArea
                                @include('partials.nav-admin')
                            @else
                                @include('partials.nav-public')
                            @endadminArea

                            @auth
                                @include('partials.nav-account')
                            @endauth
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    @include('partials.breadcrumbs', [
                        'breadcrumbs' => Breadcrumbs::current(),
                        'homeUrl' => Breadcrumbs::homeUrl(),
                    ])
                    @yield('back')

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

    @if (session('status'))
        <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3 d-print-none">
            <div class="toast text-bg-success border-0 overflow-hidden" role="status" aria-atomic="true"
                 data-bs-autohide="false">
                <div class="d-flex">
                    <div class="toast-body">{{ session('status') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="toast-progress"></div>
            </div>
        </div>
    @endif

    @adminArea
        <div class="admin-frame border border-3 border-warning pe-none d-print-none"></div>
    @endadminArea
</body>
</html>
