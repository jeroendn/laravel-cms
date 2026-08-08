@php
    /** @var \App\Models\Post $post */
@endphp

<article class="card mb-3">
    <div class="card-body">
        <h2 class="card-title mb-1">
            <a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
        </h2>
        @if ($post->published_at)
            <div class="text-secondary mb-3">
                <time datetime="{{ $post->published_at->toIso8601String() }}">
                    {{ $post->published_at->translatedFormat('j F Y') }}
                </time>
            </div>
        @endif
        <p>{{ $post->excerpt() }}</p>
        <a href="{{ route('posts.show', $post) }}">{{ __('Read more') }}</a>
    </div>
</article>
