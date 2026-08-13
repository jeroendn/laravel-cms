@php
    /** @var \App\Models\PageGroup|null $group */
@endphp

<div class="mb-3">
    <label class="form-label required" for="name">{{ __('Name') }}</label>
    <input id="name" type="text" name="name" value="{{ old('name', $group?->name) }}"
           class="form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="slug">{{ __('Slug') }}</label>
    <input id="slug" type="text" name="slug" value="{{ old('slug', $group?->slug) }}"
           class="form-control @error('slug') is-invalid @enderror"
           aria-describedby="slug-help">
    @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="slug-help" class="form-hint">
        {{ __('Leave empty to generate the slug from the name.') }}
    </small>
</div>

<div class="mb-3">
    <label class="form-label" for="parent_id">{{ __('Parent group') }}</label>
    <select id="parent_id" name="parent_id"
            class="form-select @error('parent_id') is-invalid @enderror"
            aria-describedby="parent-help">
        <option value="">{{ __('None') }}</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->id }}"
                    @selected(old('parent_id', (string) $group?->parent_id) === (string) $parent->id)>
                {{ $parent->name }}
            </option>
        @endforeach
    </select>
    @error('parent_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="parent-help" class="form-hint">
        {{ __('Only one level of nesting is allowed.') }}
    </small>
</div>

<div class="mb-3">
    <label class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="show_in_menu" value="1"
               @checked(old('show_in_menu', ($group->show_in_menu ?? false) ? '1' : ''))>
        <span class="form-check-label">{{ __('Show in menu') }}</span>
    </label>
</div>

<div class="mb-3">
    <label class="form-label" for="priority">{{ __('Priority') }}</label>
    <input id="priority" type="number" name="priority" value="{{ old('priority', (string) ($group->priority ?? 0)) }}"
           class="form-control @error('priority') is-invalid @enderror"
           aria-describedby="priority-help">
    @error('priority')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="priority-help" class="form-hint">
        {{ __('Higher priority sorts further left in the menu; equal priority sorts alphabetically.') }}
    </small>
</div>
