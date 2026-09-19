<div class="input-group">
    <label for="{{ $id }}" class="input-label">{{ $label }}</label>
    <div class="tooltip-container">
        <span class="tooltip-icon" tabindex="0" aria-label="More info">
            @include('partials.tooltip-icon')
            <span class="tooltip-text" role="tooltip">{!! $slot !!}</span>
        </span>
    </div>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $id }}"
        pattern="{{ $pattern }}"
        class="input-field @error($name) input-field--error @enderror"
        value="{{ $value }}"
        required
        @if ($readonly) readonly @endif
    >
    @error($name)
        <p class="field-error" role="alert">{{ $message }}</p>
    @enderror
</div>
