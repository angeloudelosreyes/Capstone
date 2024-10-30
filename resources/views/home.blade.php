@extends('layouts.app')
@section('container')
    <div class="row">
        @if(count($query) == 0)
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">Folders</h5>

            @foreach($query as $data)
                <div class="col-md-2 col-6 folder-card">
                    <div class="card  bg-light shadow-none" id="folder-1">
                        <div class="card-body">
                            <div class="d-flex mb-1">
                                <div class="form-check form-check-danger mb-3 fs-15 flex-grow-1">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{route('folder.show',['id' => Crypt::encryptString($data->id)])}}" ><i class="bx bx-link me-2"></i> Open Folder</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="create_files('{{Crypt::encryptString($data->id)}}','{{$data->title}}')"><i class="bx bx-upload me-2"></i> Upload Files</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="upload_encrypted_files('{{Crypt::encryptString($data->id)}}','{{$data->title}}')"><i class="bx bx-lock me-2"></i> Upload Encrypted Files</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="update_folder('{{Crypt::encryptString($data->id)}}','{{$data->title}}')"><i class="bx bx-pencil me-2"></i> Rename</a></li>
                                        <li><a class="dropdown-item" href="{{route('folder.destroy',['id' => Crypt::encryptString($data->id)])}}"><i class="bx bx-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="mb-2">
                                    <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                </div>
                                <h6 class="fs-15 folder-name">{{$data->title}}</h6>
                            </div>
                            <div class=" mt-4 text-center text-muted">
                                <span class="text-uppercase fw-bold "><b>{{$files[$data->id]}}</b> Files</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            {{$query->links()}}
        @endif
    </div>

    <!-- Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Enter Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="password" id="filePassword" class="form-control" placeholder="Password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="submitPassword">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Encrypted Files Modal -->
    <div class="modal fade" id="uploadEncryptedFilesModal" tabindex="-1" aria-labelledby="uploadEncryptedFilesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadEncryptedFilesModalLabel">Upload Encrypted Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadEncryptedFilesForm" action="{{ route('files.decrypt.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="folder_id" id="encryptedFolderId">
                        <input type="hidden" name="folder" id="encryptedFolderTitle">
                        <div class="mb-3">
                            <label for="encryptedFiles" class="form-label">Select Files</label>
                            <input type="file" class="form-control" id="encryptedFiles" name="files[]" multiple required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('custom_js')
    <script>
        function upload_encrypted_files(folderId, folderTitle) {
            document.getElementById('encryptedFolderId').value = folderId;
            document.getElementById('encryptedFolderTitle').value = folderTitle;
            $('#uploadEncryptedFilesModal').modal('show');
        }

        document.querySelectorAll('.upload-button').forEach(button => {
            button.addEventListener('click', function() {
                const folderId = this.getAttribute('data-folder-id');
                const folderTitle = this.getAttribute('data-folder-title');
                document.getElementById('folder_id').value = folderId;
                document.getElementById('folder').value = folderTitle;
                $('#create_files').modal('show');
            });
        });

        document.querySelector('input[type="file"]').addEventListener('change', function(event) {
            const files = event.target.files;
            if (files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const fileContent = e.target.result;
                    if (fileContent.startsWith('ENCRYPTED:')) {
                        document.getElementById('password-field').style.display = 'block';
                    } else {
                        document.getElementById('password-field').style.display = 'none';
                    }
                };
                reader.readAsText(file);
            }
        });
    </script>
    <script>$('.home').addClass('active')</script>
@endsection
