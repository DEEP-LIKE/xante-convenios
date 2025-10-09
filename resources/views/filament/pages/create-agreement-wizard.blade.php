<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        {{ $this->form }}
    </div>
    
    <!-- Componente de Loading Xante -->
    @livewire('xante-loading')

    @push('scripts')
    <script>
        // Escuchar el evento de actualización de query string
        document.addEventListener('livewire:init', () => {
            Livewire.on('update-query-string', (data) => {
                if (data && data.agreement) {
                    // Actualizar la URL sin recargar la página
                    const url = new URL(window.location);
                    
                    // Remover client_id si existe y agregar agreement
                    url.searchParams.delete('client_id');
                    url.searchParams.set('agreement', data.agreement);
                    
                    // Actualizar la URL
                    window.history.replaceState({}, '', url);
                    
                    console.log('URL actualizada con agreement ID:', data.agreement);
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
