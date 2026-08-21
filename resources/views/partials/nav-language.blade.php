@use('App\Support\Locales')
@php($enabled = Locales::enabled())

@if (count($enabled) > 1)
    <li class="nav-item dropdown nav-item-trailing">
        <a href="#" class="nav-link d-flex align-items-center" data-bs-toggle="dropdown"
           role="button" aria-expanded="false" aria-label="{{ __('Change language') }}">
            <i class="fa-solid fa-language fa-fw" aria-hidden="true"></i>
            <span class="ms-2">{{ Locales::label(app()->getLocale()) }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            @foreach ($enabled as $locale)
                <form method="POST" action="{{ route('language.switch', $locale) }}">
                    @csrf
                    <button type="submit" @class(['dropdown-item', 'active' => $locale === app()->getLocale()])>
                        {{ Locales::label($locale) }}
                    </button>
                </form>
            @endforeach
        </div>
    </li>
@endif
