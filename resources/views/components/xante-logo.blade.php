@props([
    'variant' => 'default', // default, white
    'size' => 'md' // sm, md, lg, xl
])

@php
$sizes = [
    'sm' => 'h-8',
    'md' => 'h-12', 
    'lg' => 'h-16',
    'xl' => 'h-20'
];

$logoSrc = $variant === 'white' 
    ? asset('images/Logo-Xante-Blanco.png')
    : asset('images/Logo-Xante.png');

$classes = 'w-auto ' . $sizes[$size];
@endphp

<img 
    src="{{ $logoSrc }}" 
    alt="Xante Logo" 
    {{ $attributes->merge(['class' => $classes]) }}
>
