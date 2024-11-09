@extends('layouts.app')
@section('container')
    <div class="row">
        <!-- Display created shareable folders -->
        @if ($createdFolders->isEmpty())
            <div class="col-12">
                <div class="alert alert-warning">You haven't created any shared folders yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">My Shared Folders and Files</h5>
            @foreach ($createdFolders as $folder)
                <div class="col-md-2 col-6 folder-card">
                    <div class="card bg-light shadow-none">
                        <div class="card-body">
                            <div class="d-flex mb-1">
                                <div class="dropdown position-absolute" style="top: 5px; right: 5px;">
                                    <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item"
                                                href="{{ route('folder.shareable.show', ['id' => Crypt::encryptString($folder->id)]) }}"><i
                                                    class="bx bx-link me-2"></i> Open Folder</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="update_shareablefolder('{{ Crypt::encryptString($folder->id) }}', '{{ $folder->title }}')"><i
                                                    class="bx bx-edit me-2"></i> Rename</a></li>
                                        <li>
                                            <form
                                                action="{{ route('folder.shareable.destroy', ['id' => Crypt::encryptString($folder->id)]) }}"
                                                method="POST" style="display: inline;"
                                                id="delete-form-{{ $folder->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="dropdown-item"
                                                    onclick="confirmDelete('{{ $folder->id }}')">
                                                    <i class="bx bx-trash me-2"></i> Delete Folder
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="text-center">
                                <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                <h6 class="fs-15 folder-name">{{ $folder->title }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{ $createdFolders->links() }}
        @endif

        <!-- Display shared files -->
        @if ($sharedFiles->isEmpty())
            <div class="col-12">
            </div>
        @else
            @foreach ($sharedFiles as $data)
                <div class="col-md-2 col-6 folder-card">
                    <div class="card bg-light shadow-none" id="folder-1">
                        <div class="card-body">
                            <div class="d-flex mb-1">
                                <div class="dropdown position-absolute" style="top: 5px; right: 5px;">
                                    <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item open-file-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($data->id) }}"
                                                data-protected="{{ $data->protected }}"
                                                data-password="{{ $data->password }}"><i class="bx bx-link me-2"></i> Open
                                                File</a></li>
                                        <li><a class="dropdown-item download-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($data->id) }}"><i
                                                    class="bx bx-download me-2"></i> Download</a></li>
                                        <li>
                                            <form
                                                action="{{ route('shared.destroy', ['id' => Crypt::encryptString($data->id)]) }}"
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

                            <div class="text-center">
                                <div class="mb-2">
                                    @php
                                        $extension = strtolower(pathinfo($data->files, PATHINFO_EXTENSION));
                                    @endphp
                                    @if (in_array($extension, ['doc', 'docx']))
                                        <i class="ri-file-word-fill align-bottom text-primary display-5"></i>
                                    @elseif(in_array($extension, ['pdf', 'ppt', 'pptx']))
                                        <i class="ri-file-pdf-line align-bottom text-danger display-5"></i>
                                    @elseif(in_array($extension, ['xls', 'xlsx']))
                                        <i class="ri-file-excel-line align-bottom text-success display-5"></i>
                                    @else
                                        <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                    @endif
                                </div>
                                <h6 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    class="fs-15 folder-name">{{ $data->files }}</h6>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
            {{ $sharedFiles->links() }}
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
                    <input type="password" id="filePassword" class="form-control" placeholder="Enter your password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitPassword">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameModalLabel">Rename File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="newFileName" class="form-control" placeholder="Enter new file name">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRenameButton">Rename</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        function downloadFile(fileId) {
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

                    return fetch(
                            `{{ url('drive/download') }}/${fileId}?password=${encodeURIComponent(password)}`, {
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('Response text:', text); // Log the response text
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
                            a.download = 'protected-file.zip'; // Use the fixed name for the downloaded file
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

        document.querySelectorAll('.download-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                downloadFile(fileId);
            });
        });

        function openFile(fileId, isProtected, hasPassword) {
            if (isProtected === 'YES' && hasPassword) {
                $('#passwordModal').modal('show');
                $('#submitPassword').off('click').on('click', function() {
                    const password = $('#filePassword').val();
                    if (!password) {
                        alert('Password is required');
                        return;
                    }
                    window.location.href =
                        `{{ url('drive/sharedShow') }}/${fileId}?password=${encodeURIComponent(password)}`;
                });
            } else {
                window.location.href = `{{ url('drive/sharedShow') }}/${fileId}`;
            }
        }

        document.querySelectorAll('.open-file-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                const isProtected = this.getAttribute('data-protected');
                const hasPassword = this.getAttribute('data-password') !== '';
                openFile(fileId, isProtected, hasPassword);
            });
        });

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}'
            });
        @endif

        document.querySelectorAll('.rename-file-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                const fileName = this.getAttribute('data-file-name'); // Get the file name

                // Set the current file name in the input field
                $('#newFileName').val(fileName);

                $('#renameModal').modal('show');

                // Handle rename confirmation
                $('#confirmRenameButton').off('click').on('click', function() {
                    const newFileName = $('#newFileName').val().trim();
                    if (!newFileName) {
                        alert('New file name is required');
                        return;
                    }

                    // Send the rename request to the server
                    fetch(`{{ url('drive/rename') }}/${fileId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                new_name: newFileName
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            $('#renameModal').modal('hide');
                            if (data.type === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Renamed',
                                    text: data.message,
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message,
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error renaming file:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to rename file.',
                            });
                        });
                });
            });
        });
    </script>
    <script>
        function confirmDelete(folderId) {
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
                    // Submit the form if the user confirmed the deletion
                    document.getElementById('delete-form-' + folderId).submit();
                }
            });
        }
    </script>
    <script>
        function downloadFile(fileId) {
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

                    return fetch(
                            `{{ url('drive/download') }}/${fileId}?password=${encodeURIComponent(password)}`, {
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('Response text:', text); // Log the response text
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
                            a.download = 'protected-file.zip'; // Use the fixed name for the downloaded file
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

        document.querySelectorAll('.download-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                downloadFile(fileId);
            });
        });
    </script>
@endsection
