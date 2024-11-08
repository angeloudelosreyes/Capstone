@extends('layouts.app')
@section('container')
    <div class="row mb-4">
        <div class="col-md-4">
            <input type="text" id="fileSearch" class="form-control" placeholder="Search Files...">
        </div>
    </div>

    <div class="row">
        @if (count($query) == 0)
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">{{ $title }}</h5>

            @php
                // Create an array to keep track of displayed filenames
                $displayedFiles = [];
            @endphp

            @foreach ($query as $data)
                @if (in_array($data->files, $displayedFiles))
                    @continue // Skip this file if it's already displayed
                @endif

                @php
                    // Add the current file name to the displayed array
                    $displayedFiles[] = $data->files;
                @endphp

                <div class="col-md-2 col-6 folder-card">
                    <div class="card bg-light shadow-none" id="folder-1">
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
                                        <li><a class="dropdown-item"
                                                href="{{ route('drive.show', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-link me-2"></i> Open File</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="share_file('{{ Crypt::encryptString($data->id) }}')"><i
                                                    class="bx bx-share me-2"></i> Share</a></li>
                                        <li><a class="dropdown-item download-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($data->id) }}"><i
                                                    class="bx bx-download me-2"></i> Download</a></li>

                                        <li><a class="dropdown-item"
                                                href="{{ route('drive.edit', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-edit me-2"></i> Edit</a></li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                onclick="renameFile('{{ Crypt::encryptString($data->id) }}', '{{ $data->files }}')">
                                                <i class="bx bx-edit-alt me-2"></i> Rename
                                            </a>
                                        </li>

                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="copyFile('{{ Crypt::encryptString($data->id) }}')"><i
                                                    class="bx bx-copy me-2"></i> Copy</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="moveFile('{{ Crypt::encryptString($data->id) }}')"><i
                                                    class="bx bx-cut me-2"></i> Move</a></li>

                                        <li><a class="dropdown-item"
                                                href="{{ route('drive.destroy', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="{{ route('drive.show', ['id' => Crypt::encryptString($data->id)]) }}"
                                    class="text-decoration-none">
                                    <div class="mb-2 folder-icon">
                                        @if ($data->extension == 'txt')
                                            <i class="ri-file-2-fill align-bottom text-default display-5"></i>
                                        @elseif($data->extension == 'pdf')
                                            <i class="ri-file-pdf-line align-bottom text-danger display-5"></i>
                                        @elseif($data->extension == 'docx')
                                            <i class="ri-file-word-fill align-bottom text-primary display-5"></i>
                                        @else
                                            <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                        @endif
                                    </div>
                                    <h6 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        class="fs-15 folder-name">{{ $data->files }}</h6>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
            <!-- Add Paste Button if there’s a copied file in session -->
            @if (session('copiedFile'))
                <div class="col-12 mb-3">
                    <button class="btn btn-success" onclick="pasteFile('{{ $folderId }}')"><i
                            class="bx bx-paste me-2"></i> Paste</button>
                </div>
            @endif
            {{ $query->links() }}
        @endif
    </div>

    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="renameForm" action="{{ route('drive.rename', 'id') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameModalLabel">Rename File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="fileId">
                        <div class="mb-3">
                            <label for="new_name" class="form-label">New File Name</label>
                            <input type="text" class="form-control" name="new_name" id="new_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Rename</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection


<script>
    // Copy a file
    function copyFile(fileId) {
        fetch(`{{ route('drive.copy', '') }}/${fileId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => response.json()).then(data => {
            if (data.type === 'success') {
                Swal.fire('Copied!', 'The file has been copied successfully.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', data.message || 'Failed to copy the file.', 'error');
            }
        }).catch(error => console.error('Error:', error));
    }

    function moveFile(fileId) {
        // Set the hidden input for fileId
        document.getElementById('fileIdToMove').value = fileId;

        const destinationFolderSelect = document.getElementById('destinationFolder');
        destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

        // Fetch the available folders
        fetch("{{ route('folders.list') }}")
            .then(response => response.json())
            .then(data => {
                destinationFolderSelect.innerHTML = '<option value="">Select a folder</option>';
                data.folders.forEach(folder => {
                    const option = document.createElement('option');
                    option.value = folder.encrypted_id;
                    option.textContent = folder.title;
                    destinationFolderSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching folders:', error));

        $('#moveFileModal').modal('show'); // Show the modal
    }

    document.getElementById('destinationFolder').addEventListener('change', function() {
        const fileId = document.getElementById('fileIdToMove').value;
        const destinationFolderId = this.value;

        // Confirm that the IDs are set
        console.log("Selected fileId:", fileId);
        console.log("Selected destinationFolderId:", destinationFolderId);

        // Only set the action URL if both IDs are present
        if (fileId && destinationFolderId) {
            const actionUrl = `{{ url('drive/move') }}/${fileId}/${destinationFolderId}`;
            console.log("Form action URL set to:", actionUrl); // Log to verify
            document.getElementById('moveFileForm').action = actionUrl;

            // Log the constructed action URL to verify
            console.log("Form action URL set to:", actionUrl);
        }
    });

    function submitMoveFileForm() {
        const fileId = document.getElementById('fileIdToMove').value;
        const destinationFolderId = document.getElementById('destinationFolder').value;

        if (fileId && destinationFolderId) {
            // Construct the route URL with the parameters
            const actionUrl = `{{ url('drive/move') }}/${fileId}/${destinationFolderId}`;
            document.getElementById('moveFileForm').action = actionUrl; // Set the form action

            console.log("Form action URL set to:", actionUrl); // Log to verify
            document.getElementById('moveFileForm').submit(); // Submit the form
        } else {
            Swal.fire('Error', 'Please select a destination folder.', 'error');
        }
    }

    // Paste the copied file into the current folder
    function pasteFile(destinationFolderId) {
        fetch(`{{ route('drive.paste', '') }}/${destinationFolderId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => response.json()).then(data => {
            if (data.type === 'success') {
                Swal.fire('Pasted!', 'The file has been pasted successfully.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', data.message || 'Failed to paste the file.', 'error');
            }
        }).catch(error => console.error('Error:', error));
    }

    function showRenameModal(id, oldName) {
        document.getElementById('fileId').value = id;
        document.getElementById('new_name').value = oldName; // Set the current name as default
        var modal = new bootstrap.Modal(document.getElementById('renameModal'));
        modal.show();
        // Update the form action URL with the encrypted ID
        document.getElementById('renameForm').action = "{{ route('drive.rename', '') }}" + '/' + id;
    }

    function renameFile(fileId, oldName) {
        Swal.fire({
            title: 'Rename File',
            input: 'text',
            inputLabel: 'Enter the new file name',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'Rename',
            showLoaderOnConfirm: true,
            preConfirm: (newName) => {
                if (!newName.trim()) {
                    Swal.showValidationMessage('File name cannot be empty');
                    return;
                }
                return fetch(`{{ url('drive/rename') }}/${fileId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            new_name: newName
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.type === 'success') {
                            Swal.fire('Renamed!', 'File has been renamed successfully.', 'success')
                                .then(() => {
                                    location.reload(); // Reload the page after successful rename
                                });
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error!', 'An error occurred while renaming the file.',
                        'error'));
            }
        });
    }

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
    document.getElementById('fileSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const files = document.querySelectorAll('.folder-card');

        files.forEach(file => {
            const fileName = file.querySelector('.folder-name').textContent.toLowerCase();
            if (fileName.includes(searchTerm)) {
                file.style.display = 'block';
            } else {
                file.style.display = 'none';
            }
        });
    });

    function copyFile(fileId) {
        console.log("Copying file with ID:", fileId);

        // Set the hidden input for fileId in the form
        document.getElementById('fileIdToCopy').value = fileId;

        const destinationFolderSelect = document.getElementById('copyDestinationFolder');
        destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

        // Fetch the available folders to populate the dropdown
        fetch("{{ route('folders.list') }}")
            .then(response => {
                console.log("Fetching folders...");
                return response.json();
            })
            .then(data => {
                console.log("Fetched folders:", data.folders);
                destinationFolderSelect.innerHTML = '<option value="">Select a folder</option>';
                data.folders.forEach(folder => {
                    const option = document.createElement('option');
                    option.value = folder.encrypted_id; // Assuming folder.encrypted_id exists
                    option.textContent = folder.title;
                    destinationFolderSelect.appendChild(option);
                });

                // Show the modal only after folders are fetched
                $('#copyFileModal').modal('show'); // Show the modal
            })
            .catch(error => {
                console.error('Error fetching folders:', error);
                Swal.fire('Error!', 'Could not fetch folders. Please try again later.', 'error');
            });
    }

    function submitCopyFileForm() {
        const form = document.getElementById('copyFileForm');
        const fileId = document.getElementById('fileIdToCopy').value;
        const destinationFolderId = document.getElementById('copyDestinationFolder').value;

        console.log("Submitting copy form with fileId:", fileId, "and destinationFolderId:", destinationFolderId);

        if (fileId && destinationFolderId) {
            // Now set the form action to paste
            form.action = `{{ url('drive/paste') }}/${destinationFolderId}`;
            form.method = 'POST'; // Ensure it's a POST request
            form.submit(); // Submit the form to paste the copied file
        } else {
            Swal.fire('Error', 'Please select a destination folder.', 'error');
        }
    }
</script>
