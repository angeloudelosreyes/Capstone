<form action="{{route('files.store')}}" enctype="multipart/form-data" method="POST">
    @csrf
    @honeypot
    <input type="hidden" id="folder_id" name="folder_id">
    <input type="hidden" id="folder" name="folder">
    <div class="modal component fade" id="create_files" tabindex="-1" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"><span id="caption"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="title" class="form-label">Upload Files (.pdf, .docx)</label>
                                <input type="file" multiple class="form-control" accept=".pdf,.docx,.txt" name="files[]">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isEncrypted" name="isEncrypted" value="1">
                                <label class="form-check-label" for="isEncrypted">
                                    Encrypt the file?
                                </label>
                            </div>
                        </div>
                        <div class="col-12" id="password-field" style="display: none;">
                            <div>
                                <label for="filePassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="filePassword" name="password" placeholder="Enter password to decrypt the file">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.getElementById('isEncrypted').addEventListener('change', function() {
        const passwordField = document.getElementById('password-field');
        if (this.checked) {
            passwordField.style.display = 'block';
        } else {
            passwordField.style.display = 'none';
        }
    });
</script>
