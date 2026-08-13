@extends('layouts.app')

@section('title', __('Edit :name', ['name' => __('page group')]))

@section('back')
    @include('partials.back-link', ['url' => route('admin.page-groups.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Edit :name', ['name' => __('page group')]) }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.page-groups.update', $group) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                @include('admin.page-groups._form', ['group' => $group])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
