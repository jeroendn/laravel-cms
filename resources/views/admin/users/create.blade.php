@extends('layouts.app')

@section('title', __('New user'))

@section('back')
    @include('partials.back-link', ['url' => route('admin.users.index')])
@endsection

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('New user') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="card">
            <div class="card-body">
                @include('admin.users._form', ['user' => null])

                <p class="text-secondary mb-0">
                    {{ __('The account is created without a password. After saving you get a link to hand to the user, so they can set one themselves.') }}
                </p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
