<x-filament-panels::page>
    {{ $this->form }}
    
    @push('scripts')
    <script>
        window.downloadDocumentById = function(documentId) {
            const url = '{{ url("/documents") }}/' + documentId + '/download';
            window.open(url, '_blank');
        };
    </script>
    @endpush
</x-filament-panels::page>
