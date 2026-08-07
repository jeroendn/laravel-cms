@php
    /** @var \App\Models\Post|null $post */
@endphp

<div class="mb-3">
    <label class="form-label required" for="title">{{ __('Title') }}</label>
    <input id="title" type="text" name="title" value="{{ old('title', $post?->title) }}"
           class="form-control @error('title') is-invalid @enderror"
           required autofocus>
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="slug">{{ __('Slug') }}</label>
    <input id="slug" type="text" name="slug" value="{{ old('slug', $post?->slug) }}"
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
    <input id="body" type="hidden" name="body" value="{{ old('body', $post?->body) }}">
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
    <label class="form-check form-switch">
        <input id="published" class="form-check-input" type="checkbox" name="published" value="1"
               @checked(old('published', $post?->isPublished() ? '1' : ''))>
        <span class="form-check-label">{{ __('Published') }}</span>
    </label>
</div>
