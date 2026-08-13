@if ($item->children === [])
    <li @class(['nav-item', 'active' => $item->active])>
        <a class="nav-link" href="{{ $item->url }}">{{ $item->label }}</a>
    </li>
@else
    <li @class(['nav-item', 'dropdown', 'active' => $item->active])>
        <a class="nav-link dropdown-toggle" href="{{ $item->url }}" data-bs-toggle="dropdown"
           role="button" aria-expanded="false">{{ $item->label }}</a>
        <div class="dropdown-menu">
            @foreach ($item->children as $child)
                @include('partials.menu-dropdown-item', ['item' => $child])
            @endforeach
        </div>
    </li>
@endif
