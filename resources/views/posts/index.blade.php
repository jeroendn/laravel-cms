@extends('layouts.app')

@section('title', __('Posts'))

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Posts') }}</h1>
    </div>

    @forelse ($posts as $post)
        @include('partials.post-card', ['post' => $post])
    @empty
        <p>{{ __('The first blog posts will appear here soon.') }}</p>
    @endforelse

    @include('partials.pagination', [
        'paginator' => $posts,
        'previousLabel' => __('Newer posts'),
        'nextLabel' => __('Older posts'),
    ])
@endsection
