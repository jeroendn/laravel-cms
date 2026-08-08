@extends('layouts.app')

@section('title', __('Edit :name', ['name' => __('user')]))

@section('back')
    @include('partials.back-link', ['url' => route('admin.users.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Edit :name', ['name' => __('user')]) }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                @include('admin.users._form', ['user' => $user])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
