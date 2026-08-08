@extends('layouts.app')

@section('title', __('New post'))

@section('back')
    @include('partials.back-link', ['url' => route('admin.posts.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
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
