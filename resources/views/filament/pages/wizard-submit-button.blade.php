{{-- Botón de Submit personalizado para el Wizard 2 --}}
<div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
    <div class="text-sm text-gray-500">
        <p>💡 <strong>Tip:</strong> Use los botones dentro de cada sección para realizar acciones específicas.</p>
    </div>
    
    <div class="flex space-x-3">
        {{-- Botón para volver al listado --}}
        <a href="/admin/wizard-resources" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver al Listado
        </a>
        
        {{-- Botón para recargar la página --}}
        <button type="button" 
                onclick="window.location.reload()" 
                class="inline-flex items-center px-4 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Actualizar
        </button>
    </div>
</div>
