@props([
    'variant' => 'primary', // primario, secundario, exito, advertencia, peligro, informacion
    'size' => 'md', // pequeño, mediano, grande
    'type' => 'button',
    'href' => null
])

@php
$baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

$variants = [
    'primary' => 'bg-[#BDCE0F] hover:bg-[#A8B80E] text-[#342970] focus:ring-[#BDCE0F]',
    'secondary' => 'bg-[#6C2582] hover:bg-[#5A1F6E] text-white focus:ring-[#6C2582]',
    'success' => 'bg-[#C9D534] hover:bg-[#B4C02E] text-[#342970] focus:ring-[#C9D534]',
    'warning' => 'bg-[#FFD729] hover:bg-[#E6C224] text-[#342970] focus:ring-[#FFD729]',
    'danger' => 'bg-[#D63B8E] hover:bg-[#C13580] text-white focus:ring-[#D63B8E]',
    'info' => 'bg-[#62257D] hover:bg-[#521F69] text-white focus:ring-[#62257D]'
];

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-2 text-base',
    'lg' => 'px-6 py-3 text-lg'
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
