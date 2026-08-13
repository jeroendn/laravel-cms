@if ($paginator->hasPages())
    <nav aria-label="{{ __('Pagination') }}">
        <ul class="pagination">
            <li class="page-item {{ $paginator->previousPageUrl() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}"
                   @unless ($paginator->previousPageUrl()) tabindex="-1" aria-disabled="true" @endunless>
                    {{ $previousLabel }}
                </a>
            </li>
            <li class="page-item {{ $paginator->nextPageUrl() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}"
                   @unless ($paginator->nextPageUrl()) tabindex="-1" aria-disabled="true" @endunless>
                    {{ $nextLabel }}
                </a>
            </li>
        </ul>
    </nav>
@endif
