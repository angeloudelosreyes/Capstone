<form action="{{route('folder.update')}}" method="POST">
    @csrf
    @honeypot
    <input type="hidden" name="old" id="old">
    <input type="hidden" name="id" id="id">
    <div class="modal fade component" id="update_folder" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Update Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="title" class="form-label">Update Folder Name</label>
                                <input type="text" class="form-control" name="new" id="new">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</form>
