<x-app-layout>
    <x-slot name="header">Documents</x-slot>

    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                <i class="fas fa-upload mr-1"></i> Upload Document
            </button>
        </div>
    </div>

    <livewire:documents.document-list />

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:documents.upload-document />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('document-uploaded', () => {
                $('#uploadModal').modal('hide');
            });
        });
    </script>
    @endpush
</x-app-layout>
