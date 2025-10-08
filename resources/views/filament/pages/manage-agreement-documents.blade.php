<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
     {{ $this->form }}
    </div>
    
    @push('scripts')
    <script>
        window.downloadDocumentById = function(documentId) {
            const url = '{{ url("/documents") }}/' + documentId + '/download';
            window.open(url, '_blank');
        };
    </script>
    @endpush
</x-filament-panels::page>
