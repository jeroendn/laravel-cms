@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <article>
        <header>
            <hgroup>
                <h1>{{ $post->title }}</h1>
                <p><small>{{ $post->published_at?->translatedFormat('j F Y') }}</small></p>
            </hgroup>
        </header>

        {{-- body_html is sanitized by HTMLPurifier (see Post::bodyHtml). --}}
        {!! $post->body_html !!}

        <footer><a href="{{ route('home') }}">{{ __('Back to overview') }}</a></footer>
    </article>
@endsection
