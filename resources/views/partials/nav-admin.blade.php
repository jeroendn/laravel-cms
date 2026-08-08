<li @class(['nav-item', 'active' => request()->routeIs('admin.dashboard')])>
    <a class="nav-link" href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.posts.*')])>
    <a class="nav-link" href="{{ route('admin.posts.index') }}">{{ __('Posts') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.users.*')])>
    <a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('Users') }}</a>
</li>
