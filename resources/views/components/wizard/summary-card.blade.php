@props([
    'title',
    'icon' => null,
    'color' => 'gray',
    'data' => [],
])

@php
    $colorStyles = [
        'primary' => ['border' => '#6C2582', 'bg' => '#f5f3ff', 'text' => '#6C2582'],
        'success' => ['border' => '#BDCE0F', 'bg' => '#f7fee7', 'text' => '#65a30d'],
        'warning' => ['border' => '#FFD729', 'bg' => '#fffbeb', 'text' => '#92400e'],
        'danger' => ['border' => '#D63B8E', 'bg' => '#fdf2f8', 'text' => '#9f1239'],
        'info' => ['border' => '#7C4794', 'bg' => '#faf5ff', 'text' => '#7C4794'],
        'gray' => ['border' => '#6b7280', 'bg' => '#f9fafb', 'text' => '#374151'],
    ];
    
    $style = $colorStyles[$color] ?? $colorStyles['gray'];
@endphp

<div style="border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-left: 4px solid {{ $style['border'] }}; background-color: {{ $style['bg'] }}; overflow: hidden; margin-bottom: 1.5rem;">
    <div style="padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 0.5rem;">
            @if($icon)
                <x-filament::icon
                    :icon="$icon"
                    style="height: 1.25rem; width: 1.25rem; color: {{ $style['text'] }};"
                />
            @endif
            <h3 style="font-weight: 700; font-size: 1.125rem; color: {{ $style['text'] }};">{{ $title }}</h3>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            {{ $slot }}
        </div>
    </div>
</div>

