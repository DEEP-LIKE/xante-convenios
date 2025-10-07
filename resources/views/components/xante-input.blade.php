@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'type' => 'text',
    'required' => false
])

@php
$inputClasses = 'block w-full rounded-lg border-gray-300 shadow-sm transition-colors duration-200 focus:border-[#BDCE0F] focus:ring-[#BDCE0F] focus:ring-1';
if($error) {
    $inputClasses = 'block w-full rounded-lg border-[#D63B8E] shadow-sm transition-colors duration-200 focus:border-[#D63B8E] focus:ring-[#D63B8E] focus:ring-1';
}
@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-[#D63B8E]"> *</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        {{ $attributes->except(['class', 'label', 'error', 'hint', 'required'])->merge(['class' => $inputClasses]) }}
    >
    
    @if($error)
        <p class="mt-1 text-sm text-[#D63B8E]">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
