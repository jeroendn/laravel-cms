@extends('layouts.app')

@section('title', $group->name)

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ $group->name }}</h1>
    </div>

    @if ($subgroups->isNotEmpty())
        <div class="mb-3">
            @foreach ($subgroups as $subgroup)
                <a href="{{ $subgroup->url() }}" class="btn me-2 mb-2">{{ $subgroup->name }}</a>
            @endforeach
        </div>
    @endif

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
