@php
    /** @var list<array{label: string, url: string|null}> $breadcrumbs */
@endphp

@if ($breadcrumbs !== [])
    <ol class="breadcrumb d-print-none" aria-label="{{ __('Breadcrumbs') }}">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}" aria-label="{{ __('Home') }}">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
            </a>
        </li>
        @foreach ($breadcrumbs as $crumb)
            <li @class(['breadcrumb-item', 'active' => $crumb['url'] === null])
                @if ($crumb['url'] === null) aria-current="page" @endif>
                @if ($crumb['url'] === null)
                    {{ $crumb['label'] }}
                @else
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
@endif
