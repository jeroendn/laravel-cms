@php
    /** @var \App\Models\Post|null $post */
@endphp

<label for="title">
    {{ __('Title') }}
    <input id="title" type="text" name="title" value="{{ old('title', $post?->title) }}"
           required autofocus
           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
    @error('title')
        <small id="title-error">{{ $message }}</small>
    @enderror
</label>

<label for="slug">
    {{ __('Slug') }}
    <input id="slug" type="text" name="slug" value="{{ old('slug', $post?->slug) }}"
           aria-describedby="slug-help"
           @error('slug') aria-invalid="true" @enderror>
    @error('slug')
        <small>{{ $message }}</small>
    @enderror
    <small id="slug-help">{{ __('Leave empty to generate the slug from the title.') }}</small>
</label>

<label for="body">{{ __('Content') }}</label>
<input id="body" type="hidden" name="body" value="{{ old('body', $post?->body) }}">
<div id="body-editor" @error('body') aria-invalid="true" @enderror></div>
@error('body')
    <small>{{ $message }}</small>
@enderror

<label for="published">
    <input id="published" type="checkbox" name="published" value="1" role="switch"
           @checked(old('published', $post?->isPublished() ? '1' : ''))>
    {{ __('Published') }}
</label>
