@extends('layouts.app')
@section('container')
    <div class="row mb-4">
        <div class="col-md-4">
            <input type="text" id="folderSearch" class="form-control" placeholder="Search Folders...">
        </div>
    </div>
    <div class="row">
        @if (count($query) == 0)
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">Folders</h5>

            @foreach ($query as $data)
                <div class="col-md-2 col-6 folder-card"> <!-- Changed to col-md-2 for 6 columns on medium screens -->
                    <div class="card bg-light shadow-none">
                        <div class="card-body text-center">
                            <div class="d-flex justify-content-between mb-1">
                                <div class="dropdown">
                                    <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item"
                                                href="{{ route('folder.show', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-link me-2"></i> Open Folder</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="create_files('{{ Crypt::encryptString($data->id) }}','{{ $data->title }}')"><i
                                                    class="bx bx-upload me-2"></i> Upload Files</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="upload_encrypted_files('{{ Crypt::encryptString($data->id) }}', '{{ $data->title }}')"><i
                                                    class="bx bx-lock me-2"></i> Upload Encrypted Files</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="share_folder('{{ Crypt::encryptString($data->id) }}','{{ $data->title }}')"><i
                                                    class="bx bx-share me-2"></i> Share Folder</a></li>
                                        <li><a class="dropdown-item download-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($data->id) }}"><i
                                                    class="bx bx-download me-2"></i> Download</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="update_folder('{{ Crypt::encryptString($data->id) }}','{{ $data->title }}')"><i
                                                    class="bx bx-pencil me-2"></i> Rename</a></li>
                                        <li>
                                            <form
                                                action="{{ route('folder.destroy', ['id' => Crypt::encryptString($data->id)]) }}"
                                                method="POST" style="display: inline;"
                                                id="delete-form-{{ $data->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="dropdown-item"
                                                    onclick="confirmDelete('{{ $data->id }}')">
                                                    <i class="bx bx-trash me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <a href="{{ route('folder.show', ['id' => Crypt::encryptString($data->id)]) }}"
                                class="text-decoration-none">
                                <div class="mb-2">
                                    <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                    <h6 class="fs-15 folder-name">{{ $data->title }}</h6>
                                </div>
                            </a>

                            <div class="mt-4 text-muted">
                                <span class="text-uppercase fw-bold"><b>{{ $files[$data->id] }}</b> Files</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            {{ $query->links() }}
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
    <div class="modal fade" id="uploadEncryptedFilesModal" tabindex="-1" aria-labelledby="uploadEncryptedFilesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadEncryptedFilesModalLabel">Upload Encrypted Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadEncryptedFilesForm" action="{{ route('files.decrypt.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="folder_id" id="encryptedFolderId">
                        <input type="hidden" name="folder" id="encryptedFolderTitle">

                        <div class="mb-3">
                            <label for="encryptedFiles" class="form-label">Select Files</label>
                            <input type="file" class="form-control" id="encryptedFiles" name="files[]" multiple
                                required>
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
    <script>
        function confirmDelete(subfolderId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + subfolderId).submit();
                }
            });
        }
    </script>
    <script>
        $('.home').addClass('active')
    </script>
    <script>
        document.getElementById('folderSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const folders = document.querySelectorAll('.folder-card');

            folders.forEach(folder => {
                const folderName = folder.querySelector('.folder-name').textContent.toLowerCase();
                if (folderName.includes(searchTerm)) {
                    folder.style.display = 'block';
                } else {
                    folder.style.display = 'none';
                }
            });
        });
    </script>
    <script>
        function downloadFolder(folderId) {
            Swal.fire({
                title: 'Enter Password',
                input: 'password',
                inputLabel: 'Password',
                inputPlaceholder: 'Enter your password',
                showCancelButton: true,
                confirmButtonText: 'Download',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Password is required');
                        return;
                    }

                    return fetch(`{{ url('folder/download') }}/${folderId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                password: password
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('Response text:', text);
                                    throw new Error(text);
                                });
                            }
                            return response.blob();
                        })
                        .then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.style.display = 'none';
                            a.href = url;
                            a.download = 'folder-download.zip';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            });
        }

        document.querySelectorAll('.download-folder-button').forEach(button => {
            button.addEventListener('click', function() {
                const folderId = this.getAttribute('data-folder-id');
                downloadFolder(folderId);
            });
        });
    </script>
@endsection
