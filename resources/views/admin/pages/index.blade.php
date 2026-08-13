@extends('layouts.app')

@section('title', __('Pages'))

@section('content')
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">{{ __('Pages') }}</h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">{{ __('New page') }}</a>
            </div>
        </div>
    </div>

    @if ($pages->isEmpty())
        <p>{{ __('No pages yet.') }}</p>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Title') }}</th>
                            <th scope="col">{{ __('Page group') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col" class="w-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td><a href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a></td>
                                <td class="text-secondary">{{ $page->group?->fullName() }}</td>
                                <td>
                                    @if ($page->isVisible())
                                        <span class="badge bg-green-lt">{{ __('Published') }}</span>
                                    @elseif ($page->isScheduled())
                                        <span class="badge bg-yellow-lt">{{ __('Scheduled') }}</span>
                                    @else
                                        <span class="badge bg-secondary-lt">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="text-secondary">
                                    @if ($page->published_at)
                                        <time datetime="{{ $page->published_at->toIso8601String() }}">
                                            {{ $page->published_at->translatedFormat('j F Y') }}
                                        </time>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap justify-content-end">
                                        @if ($page->isVisible())
                                            <a href="{{ $page->url() }}" class="btn btn-sm"
                                               target="_blank" rel="noopener"
                                               title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm"
                                           title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                              onsubmit="return confirm(@js(__('Are you sure you want to delete :name?', ['name' => $page->title])))">
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
                'paginator' => $pages,
                'previousLabel' => __('Previous'),
                'nextLabel' => __('Next'),
            ])
        </div>
    @endif
@endsection
