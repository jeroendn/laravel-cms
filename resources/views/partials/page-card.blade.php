<article class="card mb-3">
    <div class="card-body">
        <h2 class="card-title mb-1">
            <a href="{{ route('pages.show', $page) }}">{{ $page->title }}</a>
        </h2>
        @if ($page->published_at)
            <div class="text-secondary mb-3">
                <time datetime="{{ $page->published_at->toIso8601String() }}">
                    {{ $page->published_at->translatedFormat('j F Y') }}
                </time>
            </div>
        @endif
        <p>{{ $page->excerpt() }}</p>
        <a href="{{ route('pages.show', $page) }}">{{ __('Read more') }}</a>
    </div>
</article>
