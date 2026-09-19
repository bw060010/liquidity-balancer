@if (!empty($personality))
    <div class="personality" data-tier="{{ $personality['id'] ?? '' }}">
        <p class="personality-short">{{ $personality['short'] ?? '' }}</p>
        @if (!empty($personality['full']))
            <details class="personality-details">
                <summary>A word from the reef</summary>
                <p class="personality-full">{{ $personality['full'] }}</p>
            </details>
        @endif
    </div>
@endif
