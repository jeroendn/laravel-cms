@extends('layouts.app')

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Recent posts') }}</h1>
    </div>

    @forelse ($posts as $post)
        @include('partials.post-card', ['post' => $post])
    @empty
        <p>{{ __('The first blog posts will appear here soon.') }}</p>
    @endforelse

    @if ($posts->isNotEmpty())
        <a href="{{ route('posts.index') }}">
            {{ __('All posts') }}<i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>
        </a>
    @endif
@endsection
