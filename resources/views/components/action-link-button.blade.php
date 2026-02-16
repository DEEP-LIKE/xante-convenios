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
@endphp

<a 
    href="{{ $url }}"
    @if(isset($target))
    target="{{ $target }}"
    @endif
    style="
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        padding: 20px 24px;
        border: 2px solid {{ $theme['bg'] }};
        border-radius: 12px;
        background: linear-gradient(135deg, {{ $theme['bg_light'] }} 0%, {{ $theme['bg_gradient'] }} 100%);
        box-shadow: 0 4px 12px {{ $theme['shadow'] }};
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
        width: 100%;
        text-align: left;
        text-decoration: none;
    "
    onmouseover="
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 8px 24px {{ $theme['shadow'] }}';
    "
    onmouseout="
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px {{ $theme['shadow'] }}';
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
        width: 48px;
        height: 48px;
        margin-right: 16px;
        margin-bottom: 0;
        border-radius: 50%;
        background: {{ $theme['bg'] }};
        box-shadow: 0 4px 12px {{ $theme['shadow'] }};
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    ">
        <x-filament::icon 
            icon="{{ $icon }}" 
            style="
                width: 24px;
                height: 24px;
                color: white;
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            "
        />
    </div>
    
    <!-- Contenedor de texto -->
    <div style="
        text-align: left;
        position: relative;
        z-index: 1;
        flex: 1;
    ">
        <!-- Título principal -->
        <p style="
            font-size: 16px;
            font-weight: 700;
            color: {{ $theme['text'] }};
            margin: 0 0 4px 0;
            letter-spacing: -0.3px;
            line-height: 1.2;
        ">
            {{ $label }}
        </p>
        
        <!-- Subtítulo -->
        <p style="
            font-size: 13px;
            font-weight: 500;
            color: {{ $theme['text'] }};
            opacity: 0.8;
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
        height: 4px;
        background: {{ $theme['bg'] }};
        opacity: 0.8;
    "></div>
</a>
