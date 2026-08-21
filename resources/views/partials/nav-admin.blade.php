<li @class(['nav-item', 'active' => request()->routeIs('admin.dashboard')])>
    <a class="nav-link" href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.pages.*')])>
    <a class="nav-link" href="{{ route('admin.pages.index') }}">{{ __('Pages') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.page-groups.*')])>
    <a class="nav-link" href="{{ route('admin.page-groups.index') }}">{{ __('Page groups') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.users.*')])>
    <a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('Users') }}</a>
</li>
<li @class(['nav-item', 'active' => request()->routeIs('admin.settings.*')])>
    <a class="nav-link" href="{{ route('admin.settings.edit') }}">{{ __('Settings') }}</a>
</li>
