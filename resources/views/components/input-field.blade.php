@php
    $tooltipId = $id . '-tooltip';
    $describedBy = [$tooltipId];
    if ($hint) {
        $describedBy[] = $id . '-hint';
    }
    if ($errors->has($name)) {
        $describedBy[] = $id . '-error';
    }
@endphp
<div class="input-group">
    <div class="input-label-row">
        <label for="{{ $id }}" class="input-label">{{ $label }}</label>
        <div class="tooltip-container">
            <span class="tooltip-icon" tabindex="0" aria-label="More info about {{ $label }}" aria-describedby="{{ $tooltipId }}">
                @include('partials.tooltip-icon')
                <span class="tooltip-text" id="{{ $tooltipId }}" role="tooltip">{!! $slot !!}</span>
            </span>
        </div>
    </div>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $id }}"
        pattern="{{ $pattern }}"
        inputmode="decimal"
        class="input-field {{ $inputClass }} @error($name) input-field--error @enderror"
        value="{{ $value }}"
        aria-describedby="{{ implode(' ', $describedBy) }}"
        @if ($required) required @endif
        @if ($readonly) readonly @endif
    >
    @if ($hint)
        <p class="field-hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field-error" id="{{ $id }}-error" role="alert">{{ $message }}</p>
    @enderror
</div>
