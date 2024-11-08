<div class="modal fade" id="copyFileModal" tabindex="-1" aria-labelledby="copyFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copyFileModalLabel">Copy File to Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="copyFileForm" method="POST" action="{{ route('drive.paste', 'destinationFolderId') }}">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="fileIdToCopy" name="fileId"> <!-- Hidden input for fileId -->

                    <div class="mb-3">
                        <label for="destinationFolder" class="form-label">Select Destination Folder:</label>
                        <select id="copyDestinationFolder" name="destinationFolderId" class="form-select" required>
                            <option value="">Select a folder</option>
                        </select>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCopyFileForm()">Copy</button>
            </div>
        </div>
    </div>
</div>
