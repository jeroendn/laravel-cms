<li @class(['nav-item', 'active' => request()->routeIs('home')])>
    <a class="nav-link" href="{{ route('home') }}">{{ __('Home') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('posts.*')])>
    <a class="nav-link" href="{{ route('posts.index') }}">{{ __('Posts') }}</a>
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
