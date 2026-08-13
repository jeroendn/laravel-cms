@extends('layouts.app')

@section('title', $page->title)

@section('content')
    @include('partials.page-article', ['page' => $page])
@endsection
