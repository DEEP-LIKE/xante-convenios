@props([
    'variant' => 'default', // default, primary, secondary
    'padding' => 'md' // sm, md, lg
])

@php
$baseClasses = 'bg-white rounded-lg shadow-sm border transition-all duration-200';

$variants = [
    'default' => 'border-gray-200 hover:shadow-md',
    'primary' => 'border-[#BDCE0F] shadow-[#BDCE0F]/10 hover:shadow-[#BDCE0F]/20',
    'secondary' => 'border-[#6C2582] shadow-[#6C2582]/10 hover:shadow-[#6C2582]/20'
];

$paddings = [
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8'
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $paddings[$padding];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
