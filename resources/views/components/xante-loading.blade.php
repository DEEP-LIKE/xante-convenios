@props([
    'message' => 'Generando documentos...',
    'show' => false,
    'size' => 'lg'
])

<div 
    x-data="{ show: @js($show) }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
    style="z-index: 9999; display: none;"
>
    <div class="relative bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 text-center">
        <!-- Patrón decorativo de fondo -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-2xl opacity-60"></div>
        
        <!-- Contenido principal -->
        <div class="relative z-10">
            <!-- Logo Xante con animación -->
            <div class="mb-6 flex justify-center">
                <div class="relative">
                    <!-- Logo principal -->
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    
                    <!-- Círculo de carga animado -->
                    <div class="absolute inset-0 rounded-2xl">
                        <svg class="w-20 h-20 animate-spin" viewBox="0 0 24 24">
                            <circle 
                                cx="12" 
                                cy="12" 
                                r="10" 
                                stroke="url(#gradient)" 
                                stroke-width="2" 
                                fill="none" 
                                stroke-dasharray="31.416" 
                                stroke-dashoffset="31.416"
                                class="animate-pulse"
                            >
                                <animate 
                                    attributeName="stroke-dashoffset" 
                                    dur="2s" 
                                    values="31.416;0;31.416" 
                                    repeatCount="indefinite"
                                />
                            </circle>
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#BDCE0F;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#6C2582;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#7C4794;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Mensaje principal -->
            <h3 class="text-xl font-bold text-gray-800 mb-2">
                {{ $message }}
            </h3>
            
            <!-- Mensaje secundario -->
            <p class="text-gray-600 mb-6">
                Por favor, no cierre esta ventana ni navegue a otra página.
            </p>
            
            <!-- Barra de progreso animada -->
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-green-400 via-purple-500 to-indigo-600 h-2 rounded-full animate-pulse">
                    <div class="w-full h-full bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-ping"></div>
                </div>
            </div>
            
            <!-- Puntos de carga -->
            <div class="flex justify-center space-x-2">
                <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                <div class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
            </div>
            
            <!-- Información adicional -->
            <div class="mt-6 text-xs text-gray-500">
                <p>Generando documentos PDF...</p>
                <p>Este proceso puede tomar unos momentos</p>
            </div>
        </div>
        
        <!-- Efectos decorativos -->
        <div class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full opacity-20 animate-pulse"></div>
        <div class="absolute bottom-4 left-4 w-6 h-6 bg-gradient-to-br from-green-400 to-blue-500 rounded-full opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
    </div>
    
    <!-- Estilos adicionales -->
    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .animate-spin-slow {
            animation: spin-slow 3s linear infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</div>
