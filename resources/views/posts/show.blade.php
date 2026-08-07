@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <article class="card">
        <div class="card-body">
            <h1 class="card-title mb-1">{{ $post->title }}</h1>
            <div class="text-secondary mb-4">{{ $post->published_at?->translatedFormat('j F Y') }}</div>

            {{-- body_html is sanitized by HTMLPurifier (see Post::bodyHtml). --}}
            {!! $post->body_html !!}

            <p class="mt-4 mb-0"><a href="{{ route('home') }}">{{ __('Back to overview') }}</a></p>
        </div>
    </article>
@endsection
