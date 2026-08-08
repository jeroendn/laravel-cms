@extends('layouts.app')

@section('title', __('Posts'))

@section('content')
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">{{ __('Posts') }}</h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.posts.create') }}" class="btn btn-primary btn-sm">{{ __('New post') }}</a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($posts->isEmpty())
        <p>{{ __('No posts yet.') }}</p>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Title') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col" class="w-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a></td>
                                <td>
                                    @if ($post->isPublished())
                                        <span class="badge bg-green-lt">{{ __('Published') }}</span>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="text-secondary">
                                    @if ($post->published_at)
                                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                                            {{ $post->published_at->translatedFormat('j F Y') }}
                                        </time>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap justify-content-end">
                                        @if ($post->isPublished())
                                            <a href="{{ route('posts.show', $post) }}" class="btn btn-sm"
                                               target="_blank" rel="noopener"
                                               title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm"
                                           title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                              onsubmit="return confirm(@js(__('Are you sure you want to delete this post?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            @include('partials.pagination', [
                'paginator' => $posts,
                'previousLabel' => __('Previous'),
                'nextLabel' => __('Next'),
            ])
        </div>
    @endif
@endsection
