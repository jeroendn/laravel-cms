@extends('layouts.app')

@section('title', $page->title)

@section('content')
    <article>
        <div class="page-header">
            <h1 class="page-title">{{ $page->title }}</h1>
            @if ($page->published_at)
                <div class="text-secondary">
                    <time datetime="{{ $page->published_at->toIso8601String() }}">
                        {{ $page->published_at->translatedFormat('j F Y') }}
                    </time>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                {{-- body_html is sanitized by HTMLPurifier (see Page::bodyHtml). --}}
                {!! $page->body_html !!}
            </div>
        </div>
    </article>
@endsection
