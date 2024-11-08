<div class="modal fade" id="moveFileModal" tabindex="-1" aria-labelledby="moveFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moveFileModalLabel">Move File to Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="moveFileForm" method="POST">
                    @csrf
                    <input type="hidden" id="fileIdToMove" name="fileId"> <!-- Hidden input for fileId -->
                    <div class="mb-3">
                        <label for="destinationFolder" class="form-label">Select Destination Folder:</label>
                        <select id="destinationFolder" name="destinationFolderId" class="form-select" required>
                            <option value="">Select a folder</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitMoveFileForm()">Move</button>
            </div>
        </div>
    </div>
</div>
