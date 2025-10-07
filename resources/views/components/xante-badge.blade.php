@props([
    'variant' => 'primary', // primary, secondary, success, warning, danger, info
    'size' => 'md' // sm, md, lg
])

@php
$baseClasses = 'inline-flex items-center font-medium rounded-full';

$variants = [
    'primary' => 'bg-[#BDCE0F]/10 text-[#342970] border border-[#BDCE0F]/20',
    'secondary' => 'bg-[#6C2582]/10 text-[#6C2582] border border-[#6C2582]/20',
    'success' => 'bg-[#C9D534]/10 text-[#342970] border border-[#C9D534]/20',
    'warning' => 'bg-[#FFD729]/10 text-[#342970] border border-[#FFD729]/20',
    'danger' => 'bg-[#D63B8E]/10 text-[#D63B8E] border border-[#D63B8E]/20',
    'info' => 'bg-[#62257D]/10 text-[#62257D] border border-[#62257D]/20'
];

$sizes = [
    'sm' => 'px-2 py-1 text-xs',
    'md' => 'px-3 py-1 text-sm',
    'lg' => 'px-4 py-2 text-base'
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
