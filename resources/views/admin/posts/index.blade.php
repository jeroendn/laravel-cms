@extends('layouts.app')

@section('title', __('Posts'))

@section('content')
    <hgroup>
        <h1>{{ __('Posts') }}</h1>
        <p>{{ __('Manage the blog posts.') }}</p>
    </hgroup>

    @if (session('status'))
        <p><ins>{{ session('status') }}</ins></p>
    @endif

    <p><a href="{{ route('admin.posts.create') }}" role="button">{{ __('New post') }}</a></p>

    @if ($posts->isEmpty())
        <p>{{ __('No posts yet.') }}</p>
    @else
        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Date') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a></td>
                            <td>{{ $post->isPublished() ? __('Published') : __('Draft') }}</td>
                            <td>{{ $post->published_at?->translatedFormat('j F Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                      onsubmit="return confirm('{{ __('Are you sure you want to delete this post?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="outline secondary">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination', [
            'paginator' => $posts,
            'previousLabel' => __('Previous'),
            'nextLabel' => __('Next'),
        ])
    @endif
@endsection
