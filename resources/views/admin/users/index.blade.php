@extends('layouts.app')

@section('title', __('Users'))

@section('content')
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">{{ __('Users') }}</h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">{{ __('New user') }}</a>
            </div>
        </div>
    </div>

    @if (session('resetLink'))
        <div class="alert alert-info">
            <h2 class="alert-title">{{ __('Password reset link') }}</h2>
            <p>
                {{ __('Give this link to the user so they can set their own password.') }}
                {{ __('This password reset link will expire in :count minutes.', ['count' => config()->integer('auth.passwords.users.expire')]) }}
            </p>
            <div class="input-group">
                <input id="reset-link" type="text" class="form-control" readonly onclick="this.select()"
                       value="{{ session('resetLink') }}" aria-label="{{ __('Password reset link') }}">
                <button id="reset-link-copy" type="button" class="btn"
                        title="{{ __('Click to copy') }}" aria-label="{{ __('Click to copy') }}">
                    <i class="fa-solid fa-copy" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Email Address') }}</th>
                        <th scope="col">{{ __('Created') }}</th>
                        <th scope="col">{{ __('Last active') }}</th>
                        <th scope="col" class="w-1 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.edit', $user) }}">{{ $user->name }}</a>
                                @if ($user->id === auth()->id())
                                    <span class="badge bg-secondary-lt ms-1">{{ __('You') }}</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $user->email }}</td>
                            <td class="text-secondary">
                                @if ($user->created_at)
                                    <time datetime="{{ $user->created_at->toIso8601String() }}">
                                        {{ $user->created_at->translatedFormat('j F Y') }}
                                    </time>
                                @endif
                            </td>
                            <td class="text-secondary">
                                @if (in_array($user->id, $onlineIds, true))
                                    <span class="status status-green">
                                        <span class="status-dot status-dot-animated"></span>{{ __('Online now') }}
                                    </span>
                                @elseif ($user->last_active_at)
                                    <time datetime="{{ $user->last_active_at->toIso8601String() }}"
                                          title="{{ $user->last_active_at->translatedFormat('j F Y H:i') }}">
                                        {{ $user->last_active_at->diffForHumans() }}
                                    </time>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap justify-content-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm"
                                       title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.users.reset-link', $user) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm"
                                                title="{{ __('Generate password reset link') }}"
                                                aria-label="{{ __('Generate password reset link') }}">
                                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm(@js(__('Are you sure you want to delete :name?', ['name' => $user->name])))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
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
            'paginator' => $users,
            'previousLabel' => __('Previous'),
            'nextLabel' => __('Next'),
        ])
    </div>
@endsection
