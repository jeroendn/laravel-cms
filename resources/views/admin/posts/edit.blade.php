@extends('layouts.app')

@section('title', __('Edit post'))

@section('content')
    <h1>{{ __('Edit post') }}</h1>

    <form method="POST" action="{{ route('admin.posts.update', $post) }}">
        @csrf
        @method('PUT')
        @include('admin.posts._form', ['post' => $post])
        <button type="submit">{{ __('Save') }}</button>
    </form>

    <a href="{{ route('admin.posts.index') }}">{{ __('Back to overview') }}</a>
@endsection
