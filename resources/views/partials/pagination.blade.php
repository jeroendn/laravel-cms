@if ($paginator->hasPages())
    <nav aria-label="{{ __('Pagination') }}">
        <ul>
            <li>
                @if ($paginator->previousPageUrl())
                    <a href="{{ $paginator->previousPageUrl() }}">{{ $previousLabel }}</a>
                @endif
            </li>
            <li>
                @if ($paginator->nextPageUrl())
                    <a href="{{ $paginator->nextPageUrl() }}">{{ $nextLabel }}</a>
                @endif
            </li>
        </ul>
    </nav>
@endif
