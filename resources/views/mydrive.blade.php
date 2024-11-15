@extends('layouts.app')
@section('container')
    <div class="row mb-4">
        <div class="col-md-4">
            <input type="text" id="fileSearch" class="form-control" placeholder="Search Files...">
        </div>
    </div>
    <div class="row">
        <script>
            // Check if the skipNotification flag is set
            if (sessionStorage.getItem('skipNotification') === 'true') {
                // Hide notifications
                document.addEventListener('DOMContentLoaded', function() {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(alert => alert.style.display = 'none');
                });
                // Clear the flag for future visits
                sessionStorage.removeItem('skipNotification');
            }
        </script>
        @if (count($query) == 0)
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">{{ $title }}</h5>


            @foreach ($query as $data)
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
                                        <li>
                                            <form
                                                action="{{ route('drive.destroy', ['id' => Crypt::encryptString($data->id)]) }}"
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
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                onclick="viewFileDetails('{{ Crypt::encryptString($data->id) }}')">
                                                <i class="bx bx-info-circle me-2"></i> View Details
                                            </a>

                                        </li>
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

@section('custom_js')
    <script>
        // Copy a file
        function copyFile(fileId) {
            console.log("Copying file with ID:", fileId);

            // Set the hidden input for fileId in the form
            document.getElementById('fileIdToCopy').value = fileId;

            const destinationFolderSelect = document.getElementById('copyDestinationFolder');
            destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

            // Fetch the available folders and subfolders to populate the dropdown
            fetch("{{ route('folders.list') }}")
                .then(response => {
                    console.log("Fetching folders...");
                    return response.json();
                })
                .then(data => {
                    destinationFolderSelect.innerHTML = '<option value="">Select a folder or subfolder</option>';

                    // Check if the folders data structure matches and iterate through folders
                    if (data.folders && data.folders.folders) {
                        data.folders.folders.forEach(folder => {
                            const option = document.createElement('option');
                            option.value = folder.encrypted_id; // Assuming folder.encrypted_id exists
                            option.textContent = folder.title;
                            destinationFolderSelect.appendChild(option);
                        });
                    } else {
                        console.error('Folders data structure not as expected:', data);
                    }

                    // Check if the subfolders data structure matches and iterate through subfolders
                    if (data.folders && data.folders.subfolders) {
                        data.folders.subfolders.forEach(subfolder => {
                            const option = document.createElement('option');
                            option.value = subfolder.encrypted_id;
                            option.textContent = `${subfolder.name} (Subfolder)`;
                            destinationFolderSelect.appendChild(option);
                        });
                    } else {
                        console.error('Subfolders data structure not as expected:', data);
                    }

                    // Show the modal only after folders and subfolders are fetched
                    $('#copyFileModal').modal('show');
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

    <script>
        function moveFile(fileId) {
            // Set the hidden input for fileId
            document.getElementById('fileIdToMove').value = fileId;

            const destinationFolderSelect = document.getElementById('destinationFolder');
            destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

            // Fetch the available folders and subfolders
            fetch("{{ route('folders.list') }}")
                .then(response => response.json())
                .then(data => {
                    destinationFolderSelect.innerHTML = '<option value="">Select a folder or subfolder</option>';

                    // Check if the folders data structure matches and iterate through folders
                    if (data.folders && data.folders.folders) {
                        data.folders.folders.forEach(folder => {
                            const option = document.createElement('option');
                            option.value = folder.encrypted_id;
                            option.textContent = folder.title;
                            destinationFolderSelect.appendChild(option);
                        });
                    } else {
                        console.error('Folders data structure not as expected:', data);
                    }

                    // Check if the subfolders data structure matches and iterate through subfolders
                    if (data.folders && data.folders.subfolders) {
                        data.folders.subfolders.forEach(subfolder => {
                            const option = document.createElement('option');
                            option.value = subfolder.encrypted_id;
                            option.textContent = `${subfolder.name} (Subfolder)`;
                            destinationFolderSelect.appendChild(option);
                        });
                    } else {
                        console.error('Subfolders data structure not as expected:', data);
                    }
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
    </script>

    <script>
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
    </script>

    <script>
        function showRenameModal(id, oldName) {
            document.getElementById('fileId').value = id;
            document.getElementById('new_name').value = oldName; // Set the current name as default
            var modal = new bootstrap.Modal(document.getElementById('renameModal'));
            modal.show();
            // Update the form action URL with the encrypted ID
            document.getElementById('renameForm').action = `{{ route('drive.rename', '') }}/${id}`;
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

        document.getElementById('fileSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const files = document.querySelectorAll('.folder-card');

            console.log('Searching for:', searchTerm); // Debug log

            files.forEach(file => {
                const fileName = file.querySelector('.folder-name').textContent.toLowerCase();
                if (fileName.includes(searchTerm)) {
                    file.style.display = 'block';
                } else {
                    file.style.display = 'none';
                }
            });
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
                    // Submit the form if the user confirmed the deletion
                    document.getElementById('delete-form-' + subfolderId).submit();
                }
            });
        }
    </script>
    <script>
        // Updated AJAX request URL in `viewFileDetails` function
        function viewFileDetails(encryptedFileId) {
            $.ajax({
                url: `/files/details/${encryptedFileId}`,
                method: 'GET',
                success: function(response) {
                    if (response.file) {
                        const createdAt = new Date(response.file.created_at).toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: 'numeric',
                            hour12: true
                        });
                        const updatedAt = new Date(response.file.updated_at).toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: 'numeric',
                            hour12: true
                        });

                        $('#file-details-modal .modal-body').html(`
                    <p><strong>File Name:</strong> ${response.file.files}</p>
                    <p><strong>Size:</strong> ${response.file.size} bytes</p>
                    <p><strong>Extension:</strong> ${response.file.extension}</p>
                    <p><strong>Protected:</strong> ${response.file.protected}</p>
                    <p><strong>Password:</strong> ${response.file.password ? 'Yes' : 'No'}</p>
                    <p><strong>Created At:</strong> ${createdAt}</p>
                    <p><strong>Updated At:</strong> ${updatedAt}</p>
                `);
                        $('#file-details-modal').modal('show');
                    } else {
                        alert('File details not found');
                    }
                },
                error: function() {
                    alert('Could not retrieve file details.');
                }
            });
        }
    </script>
    <script>
        public

        function showFileDetails($id) {
            // Decrypt the file ID
            try {
                $fileId = Crypt::decryptString($id);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt file ID: '.$e - > getMessage());
                return response() - > json(['error' => 'Invalid file ID'], 400);
            }

            // Fetch the file details
            $file = UsersFolderFile::where('id', $fileId) - > first();

            if (!$file) {
                Log::error("File not found for ID: $fileId");
                return response() - > json(['error' => 'File not found'], 404);
            }

            // Return the file details as a JSON response
            return response() - > json(['file' => $file], 200);
        }
    </script>
@endsection
