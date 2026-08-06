@extends('layouts.app')

@section('content')
    <hgroup>
        <h1>{{ config('app.name') }}</h1>
    </hgroup>

    @forelse ($posts as $post)
        <article>
            <header>
                <hgroup>
                    <h2><a href="{{ route('posts.show', $post) }}" class="contrast">{{ $post->title }}</a></h2>
                    <p><small>{{ $post->published_at?->translatedFormat('j F Y') }}</small></p>
                </hgroup>
            </header>
            <p>{{ $post->excerpt() }}</p>
            <footer><a href="{{ route('posts.show', $post) }}">{{ __('Read more') }}</a></footer>
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
