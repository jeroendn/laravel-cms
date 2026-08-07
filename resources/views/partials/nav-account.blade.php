<li class="nav-item dropdown ms-md-auto">
    <a href="#" class="nav-link d-flex align-items-center" data-bs-toggle="dropdown"
       role="button" aria-expanded="false" aria-label="{{ __('Open user menu') }}">
        <span class="avatar avatar-sm">
            <i class="fa-solid fa-user" aria-hidden="true"></i>
        </span>
        <span class="ms-2 d-md-none">{{ __('Account') }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        @adminArea
            <a class="dropdown-item" href="{{ route('home') }}">
                <i class="fa-solid fa-globe fa-fw me-2" aria-hidden="true"></i>{{ __('View site') }}
            </a>
        @else
            <a class="dropdown-item" href="{{ route('admin.posts.index') }}">
                <i class="fa-solid fa-gear fa-fw me-2" aria-hidden="true"></i>{{ __('Administration') }}
            </a>
        @endadminArea
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item">
                <i class="fa-solid fa-right-from-bracket fa-fw me-2" aria-hidden="true"></i>{{ __('Logout') }}
            </button>
        </form>
    </div>
</li>
