@props([])

@php
    $isArrayField = str_contains($name ?? '', '[]');

    $selectId = $id ?? ($isArrayField ? null : str_replace(['[', ']'], ['_', ''], $name));

    $hasError = $errors->has($name);

    $select2Attrs = $useSelect2 ? ['data-control' => 'select2'] : [];
@endphp

<div class="mb-0">
    @if ($label)
        <label @if ($selectId) for="{{ $selectId }}" @endif class="form-label {{ $required ? 'required' : '' }}">
            {{ $label }}
        </label>
    @endif

    @if ($hint)
        <div class="text-muted fs-7 mb-2">{{ $hint }}</div>
    @endif

    <select @if ($selectId) id="{{ $selectId }}" @endif name="{{ $name }}" {{ $required ? 'required' : '' }}
        @if ($placeholder) data-placeholder="{{ $placeholder }}" @endif
        @if ($dropdownParent) data-dropdown-parent="{{ $dropdownParent }}" @endif
        {{ $attributes->merge(
            array_merge($select2Attrs, [
                'class' => 'form-select form-select-solid ' . ($hasError ? 'is-invalid' : ''),
            ]),
        ) }}>
        @if ($placeholder)
            <option value=""></option>
        @endif

        @foreach ($options as $value => $text)
            <option value="{{ $value }}" {{ old($name, $selected) == $value ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
