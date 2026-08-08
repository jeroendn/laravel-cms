@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <article>
        <div class="page-header">
            <h1 class="page-title">{{ $post->title }}</h1>
            @if ($post->published_at)
                <div class="text-secondary">
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('j F Y') }}
                    </time>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                {{-- body_html is sanitized by HTMLPurifier (see Post::bodyHtml). --}}
                {!! $post->body_html !!}
            </div>
        </div>
    </article>
@endsection
