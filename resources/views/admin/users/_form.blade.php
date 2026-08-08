@php
    /** @var \App\Models\User|null $user */
@endphp

<div class="mb-3">
    <label class="form-label required" for="name">{{ __('Name') }}</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user?->name) }}"
           class="form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label required" for="email">{{ __('Email Address') }}</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}"
           class="form-control @error('email') is-invalid @enderror"
           required autocomplete="off" aria-describedby="email-help">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small id="email-help" class="form-hint">
        {{ __('The e-mail address is the login name.') }}
    </small>
</div>
