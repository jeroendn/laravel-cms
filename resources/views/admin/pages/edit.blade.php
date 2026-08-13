@extends('layouts.app')

@section('title', __('Edit :name', ['name' => __('page')]))

@section('back')
    @include('partials.back-link', ['url' => route('admin.pages.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Edit :name', ['name' => __('page')]) }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.pages.update', $page) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                @include('admin.pages._form', ['page' => $page])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
