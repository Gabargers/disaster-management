@props([])

@php
    $inputId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div class="mb-0">
    @if ($label)
        <label for="{{ $inputId }}" class="form-label {{ $required ? 'required' : '' }}">
            {{ $label }}
        </label>
    @endif

    @if ($hint)
        <div class="text-muted fs-7 mb-2">{{ $hint }}</div>
    @endif

    <input id="{{ $inputId }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        @if (!is_null($placeholder)) placeholder="{{ $placeholder }}" @endif
        @if (!is_null($min)) min="{{ $min }}" @endif
        @if (!is_null($max)) max="{{ $max }}" @endif
        @if (!is_null($step)) step="{{ $step }}" @endif {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $attributes->merge([
            'class' => 'form-control form-control-solid ' . ($hasError ? 'is-invalid' : ''),
        ]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
