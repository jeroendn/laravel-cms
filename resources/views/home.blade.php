@extends('layouts.app')

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Recent posts') }}</h1>
    </div>

    @forelse ($posts as $post)
        <article class="card mb-3">
            <div class="card-body">
                <h2 class="card-title mb-1">
                    <a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
                </h2>
                <div class="text-secondary mb-3">{{ $post->published_at?->translatedFormat('j F Y') }}</div>
                <p>{{ $post->excerpt() }}</p>
                <a href="{{ route('posts.show', $post) }}">{{ __('Read more') }}</a>
            </div>
        </article>
    @empty
        <p>{{ __('The first blog posts will appear here soon.') }}</p>
    @endforelse

    @include('partials.pagination', [
        'paginator' => $posts,
        'previousLabel' => __('Newer posts'),
        'nextLabel' => __('Older posts'),
    ])
@endsection
