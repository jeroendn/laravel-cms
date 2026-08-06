@extends('layouts.app')

@section('title', __('New post'))

@section('content')
    <h1>{{ __('New post') }}</h1>

    <form method="POST" action="{{ route('admin.posts.store') }}">
        @csrf
        @include('admin.posts._form', ['post' => null])
        <button type="submit">{{ __('Save') }}</button>
    </form>

    <a href="{{ route('admin.posts.index') }}">{{ __('Back to overview') }}</a>
@endsection
