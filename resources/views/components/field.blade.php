@props(['label' => null, 'name', 'hint' => null])
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))
<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-1.5 text-xs text-mist">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>
