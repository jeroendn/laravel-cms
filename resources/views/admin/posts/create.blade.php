@extends('layouts.app')

@section('title', __('New post'))

@section('content')
    <div class="page-header d-print-none">
        <a href="{{ route('admin.posts.index') }}"
           class="text-secondary d-inline-flex align-items-center mb-2">
            <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>{{ __('Back') }}
        </a>
        <h1 class="page-title">{{ __('New post') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.posts.store') }}">
        @csrf

        <div class="card">
            <div class="card-body">
                @include('admin.posts._form', ['post' => null])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
