@extends('layouts.app')

@section('title', __('Edit post'))

@section('content')
    <div class="page-header d-print-none">
        <a href="{{ route('admin.posts.index') }}"
           class="text-secondary d-inline-flex align-items-center mb-2">
            <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>{{ __('Back') }}
        </a>
        <h1 class="page-title">{{ __('Edit post') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.posts.update', $post) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                @include('admin.posts._form', ['post' => $post])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
