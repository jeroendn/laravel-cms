@extends('layouts.app')

@section('content')
    @isset($page)
        @include('partials.page-article', ['page' => $page])
    @endisset
@endsection
