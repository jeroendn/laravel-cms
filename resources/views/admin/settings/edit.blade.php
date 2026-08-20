@extends('layouts.app')

@section('title', __('Settings'))

@section('content')
    <div class="page-header d-print-none">
        <h1 class="page-title">{{ __('Settings') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">{{ __('Branding') }}</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="site_name">{{ __('Site name') }}</label>
                    <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}"
                           class="form-control @error('site_name') is-invalid @enderror"
                           placeholder="{{ config()->string('app.name') }}" autofocus>
                    @error('site_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="primary_color">{{ __('Primary color') }}</label>
                    <div class="input-group">
                        <input id="primary_color" type="color" name="primary_color"
                               value="{{ old('primary_color', $settings->primary_color) }}"
                               class="form-control form-control-color @error('primary_color') is-invalid @enderror">
                        <input id="primary_color_hex" type="text" class="form-control" spellcheck="false"
                               value="{{ old('primary_color', $settings->primary_color) }}"
                               aria-label="{{ __('Primary color') }}">
                    </div>
                    @error('primary_color')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">{{ __('Access') }}</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="under_construction" value="1"
                               aria-describedby="under-construction-help"
                               @checked(old('under_construction', $settings->under_construction ? '1' : ''))>
                        <span class="form-check-label">{{ __('Under construction') }}</span>
                    </label>
                    <small id="under-construction-help" class="form-hint">
                        {{ __('The site will not be publicly available and visitors will see a construction page. Admins can still see the public pages when logged in.') }}
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_login_link" value="1"
                               aria-describedby="show-login-link-help"
                               @checked(old('show_login_link', $settings->show_login_link ? '1' : ''))>
                        <span class="form-check-label">{{ __('Show login link') }}</span>
                    </label>
                    <small id="show-login-link-help" class="form-hint">
                        {{ __('Adds a login link to the menu. Without it :url stays reachable, it is just not advertised.', ['url' => route('login')]) }}
                    </small>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">{{ __('Languages') }}</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-label required">{{ __('Available languages') }}</div>
                    @foreach ($locales as $code => $label)
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="locales[]" value="{{ $code }}"
                                   @checked(in_array($code, (array) old('locales', $settings->locales), true))>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                    @error('locales')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="form-hint">
                        {{ __('With more than one language, visitors get a language picker in the menu. It translates the site itself, never the content you typed.') }}
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label required" for="default_locale">{{ __('Default language') }}</label>
                    <select id="default_locale" name="default_locale"
                            class="form-select @error('default_locale') is-invalid @enderror"
                            aria-describedby="default-locale-help">
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}"
                                    @selected(old('default_locale', $settings->default_locale) === $code)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_locale')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small id="default-locale-help" class="form-hint">
                        {{ __('What a visitor without a preference gets. Must be one of the available languages.') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
@endsection
