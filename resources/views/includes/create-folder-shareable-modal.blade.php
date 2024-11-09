<form action="{{ route('folder.shareable.store') }}" method="POST">
    @csrf
    @honeypot
    <div class="modal component fade" id="create_folder_shareable" tabindex="-1"
        aria-labelledby="createShareableFolderLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createShareableFolderLabel">Create Shareable Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="shareable_title" class="form-label">Folder Name</label>
                                <input type="text" class="form-control" name="title" id="shareable_title" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Create</button>
                </div>
            </div>
        </div>
    </div>
</form>
