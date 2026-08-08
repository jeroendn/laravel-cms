@extends('layouts.app')

@section('title', __('Edit post'))

@section('back')
    @include('partials.back-link', ['url' => route('admin.posts.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
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
