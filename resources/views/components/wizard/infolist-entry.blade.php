@props(['label', 'value' => null])

<div style="display: flex; flex-direction: column; gap: 0.25rem;">
    <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">
        {{ $label }}
    </dt>
    <dd style="font-size: 0.875rem; font-weight: 600; color: #111827;">
        @if($value)
            {{ $value }}
        @else
            <span style="color: #9ca3af; font-style: italic;">N/A</span>
        @endif
    </dd>
</div>

