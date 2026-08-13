@extends('layouts.app')

@section('title', __('Page groups'))

@section('content')
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">{{ __('Page groups') }}</h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.page-groups.create') }}" class="btn btn-primary btn-sm">{{ __('New page group') }}</a>
            </div>
        </div>
    </div>

    @if ($groups->isEmpty())
        <p>{{ __('No page groups yet.') }}</p>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Name') }}</th>
                            <th scope="col">{{ __('Parent group') }}</th>
                            <th scope="col">{{ __('Show in menu') }}</th>
                            <th scope="col">{{ __('Priority') }}</th>
                            <th scope="col" class="w-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            <tr>
                                <td><a href="{{ route('admin.page-groups.edit', $group) }}">{{ $group->name }}</a></td>
                                <td class="text-secondary">{{ $group->parent?->name }}</td>
                                <td>
                                    @if ($group->show_in_menu)
                                        <i class="fa-solid fa-check text-success" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ __('Yes') }}</span>
                                    @else
                                        <span class="visually-hidden">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-secondary">{{ $group->priority }}</td>
                                <td>
                                    <div class="btn-list flex-nowrap justify-content-end">
                                        <a href="{{ route('admin.page-groups.edit', $group) }}" class="btn btn-sm"
                                           title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.page-groups.destroy', $group) }}"
                                              onsubmit="return confirm(@js(__('Are you sure you want to delete :name?', ['name' => $group->name])))">
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
                'paginator' => $groups,
                'previousLabel' => __('Previous'),
                'nextLabel' => __('Next'),
            ])
        </div>
    @endif
@endsection
