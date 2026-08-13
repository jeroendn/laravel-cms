@php
    /** @var \App\Models\Page|null $page */
@endphp

<div class="mb-3">
    <label class="form-label required" for="title">{{ __('Title') }}</label>
    <input id="title" type="text" name="title" value="{{ old('title', $page?->title) }}"
           class="form-control @error('title') is-invalid @enderror"
           required autofocus>
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="slug">{{ __('Slug') }}</label>
    <input id="slug" type="text" name="slug" value="{{ old('slug', $page?->slug) }}"
           class="form-control @error('slug') is-invalid @enderror"
           aria-describedby="slug-help">
    @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="slug-help" class="form-hint">
        {{ __('Leave empty to generate the slug from the title.') }}
    </small>
</div>

<div class="mb-3">
    {{-- Not a <label>: the only focusable target would be the hidden input.
         app.js points the editor at this id with aria-labelledby instead. --}}
    <div id="body-label" class="form-label required">{{ __('Content') }}</div>
    <input id="body" type="hidden" name="body" value="{{ old('body', $page?->body) }}">
    {{-- Quill turns #body-editor into .ql-container and inserts .ql-toolbar as a
         sibling, so the invalid state has to go on a wrapper around both. --}}
    <div class="@error('body') is-invalid @enderror">
        <div id="body-editor"></div>
    </div>
    @error('body')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="published_at">{{ __('Publication date') }}</label>
    <input id="published_at" type="date" name="published_at"
           value="{{ old('published_at', $page?->published_at?->format('Y-m-d')) }}"
           class="form-control @error('published_at') is-invalid @enderror"
           aria-describedby="published-at-help">
    @error('published_at')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="published-at-help" class="form-hint">
        {{ __('Leave empty to save as a draft; a future date schedules the page.') }}
    </small>
</div>
