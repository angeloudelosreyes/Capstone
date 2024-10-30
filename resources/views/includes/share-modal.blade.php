<form action="{{route('shared.store')}}" method="POST">
    @csrf
    @honeypot
    <input type="hidden" name="users_folder_files_id" id="users_folder_files_id">
    <div class="modal component fade" id="shared_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog content">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Share File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="title" class="form-label">Category</label>
                                <select name="category" class="form-control" id="category">
                                    <option value="Individual">Individual</option>
                                    @if(auth()->user()->roles == 'ADMIN')
                                    <optgroup label="Department">
                                        <option value="CCS">CCS</option>
                                        <option value="CTE">CTE</option>
                                        <option value="CBE">CBE</option>
                                    </optgroup>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-12" id="show_email">
                            <div>
                                <label for="title" class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email">
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