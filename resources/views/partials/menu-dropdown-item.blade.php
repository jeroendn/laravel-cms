@if ($item->children === [])
    <a @class(['dropdown-item', 'active' => $item->active]) href="{{ $item->url }}">{{ $item->label }}</a>
@else
    <div class="dropend">
        <a class="dropdown-item dropdown-toggle" href="{{ $item->url }}" data-submenu
           role="button" aria-expanded="false">{{ $item->label }}</a>
        <div class="dropdown-menu">
            @foreach ($item->children as $child)
                <a @class(['dropdown-item', 'active' => $child->active]) href="{{ $child->url }}">{{ $child->label }}</a>
            @endforeach
        </div>
    </div>
@endif
