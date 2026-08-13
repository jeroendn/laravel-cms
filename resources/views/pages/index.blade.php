@extends('layouts.app')

@section('title', __('Pages'))

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Pages') }}</h1>
    </div>

    @forelse ($pages as $page)
        @include('partials.page-card', ['page' => $page])
    @empty
        <p>{{ __('The first pages will appear here soon.') }}</p>
    @endforelse

    @include('partials.pagination', [
        'paginator' => $pages,
        'previousLabel' => __('Newer pages'),
        'nextLabel' => __('Older pages'),
    ])
@endsection
