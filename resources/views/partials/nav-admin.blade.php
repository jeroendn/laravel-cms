<li @class(['nav-item', 'active' => request()->routeIs('admin.posts.*')])>
    <a class="nav-link" href="{{ route('admin.posts.index') }}">{{ __('Posts') }}</a>
</li>
