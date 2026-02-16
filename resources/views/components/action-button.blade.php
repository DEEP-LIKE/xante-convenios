@php
    $colors = [
        'success' => [
            'bg' => '#10b981',
            'bg_light' => '#ecfdf5',
            'bg_gradient' => '#d1fae5',
            'text' => '#047857',
            'shadow' => 'rgba(16, 185, 129, 0.3)',
        ],
        'info' => [
            'bg' => '#3b82f6',
            'bg_light' => '#eff6ff',
            'bg_gradient' => '#dbeafe',
            'text' => '#1e40af',
            'shadow' => 'rgba(59, 130, 246, 0.3)',
        ],
        'primary' => [
            'bg' => '#8b5cf6',
            'bg_light' => '#f5f3ff',
            'bg_gradient' => '#ede9fe',
            'text' => '#6d28d9',
            'shadow' => 'rgba(139, 92, 246, 0.3)',
        ],
    ];

    $theme = $colors[$color] ?? $colors['primary'];
    $prevent = $prevent ?? true;
@endphp

<button 
    type="button"
    @if(isset($alpine_action))
        x-on:click="{{ $alpine_action }}"
    @else
        wire:click="{{ $prevent ? 'prevent.' : '' }}{{ $action }}"
    @endif
    @if(isset($confirm))
    wire:confirm="{{ $confirm }}"
    @endif
    style="
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px 24px;
        border: 3px solid {{ $theme['bg'] }};
        border-radius: 16px;
        background: linear-gradient(135deg, {{ $theme['bg_light'] }} 0%, {{ $theme['bg_gradient'] }} 100%);
        box-shadow: 0 4px 20px {{ $theme['shadow'] }};
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        min-height: 200px;
        width: 100%; /* Make button take full width */
        text-align: center; /* Ensure content is centered */
    "
    onmouseover="
        this.style.transform = 'translateY(-8px) scale(1.02)';
        this.style.boxShadow = '0 12px 40px {{ $theme['shadow'] }}';
        this.querySelector('.icon-circle').style.transform = 'scale(1.15) rotate(360deg)';
    "
    onmouseout="
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = '0 4px 20px {{ $theme['shadow'] }}';
        this.querySelector('.icon-circle').style.transform = 'scale(1) rotate(0deg)';
    "
>
    <!-- Efecto de brillo decorativo -->
    <div style="
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, {{ $theme['bg'] }}15 0%, transparent 70%);
        pointer-events: none;
    "></div>

    <!-- Icono circular con animación -->
    <div class="icon-circle" style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 64px; /* Reducido ligeramente para evitar desbordes visuales */
        height: 64px;
        margin-bottom: 16px;
        border-radius: 50%;
        background: {{ $theme['bg'] }};
        box-shadow: 0 8px 24px {{ $theme['shadow'] }};
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
        flex-shrink: 0; /* Evitar que se aplaste */
    ">
        <x-filament::icon 
            icon="{{ $icon }}" 
            style="
                width: 32px;
                height: 32px;
                color: white;
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            "
        />
    </div>
    
    <!-- Contenedor de texto -->
    <div style="
        text-align: center;
        position: relative;
        z-index: 1;
    ">
        <!-- Título principal -->
        <p style="
            font-size: 18px;
            font-weight: 700;
            color: {{ $theme['text'] }};
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
            line-height: 1.3;
        ">
            {{ $label }}
        </p>
        
        <!-- Subtítulo -->
        <p style="
            font-size: 14px;
            font-weight: 500;
            color: {{ $theme['text'] }};
            opacity: 0.75;
            margin: 0;
            line-height: 1.4;
        ">
            {{ $sublabel }}
        </p>
    </div>

    <!-- Indicador visual inferior -->
    <div style="
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: {{ $theme['bg'] }};
        opacity: 0.8;
    "></div>
</button>