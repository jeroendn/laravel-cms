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
