@extends('layouts.app')
@section('container')
    @include('includes.create-sub-folder-modal') <!-- Ensure this modal handles creating subfolders -->

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

            @foreach ($query as $data)
                @php
                    // Check for subfolders of this parent folder
                    $subfolders = DB::table('subfolders')
                        ->where('parent_folder_id', $data->id)
                        ->get();
                @endphp

                <div class="col-md-2 col-6 folder-card">
                    <div class="card bg-light shadow-none" id="folder-{{ $data->id }}">
                        <div class="card-body">
                            <div class="d-flex mb-1">
                                <div class="form-check form-check-danger mb-3 fs-15 flex-grow-1"></div>
                                <div class="dropdown">
                                    <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item"
                                                href="{{ route('drive.show', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-link me-2"></i> Open Folder</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="createSubFolder('{{ $data->id }}')"><i
                                                    class="bx bx-folder-plus me-2"></i> Create Subfolder</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="confirmDelete('{{ $data->id }}')"><i
                                                    class="bx bx-trash me-2"></i> Delete Folder</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="{{ route('drive.show', ['id' => Crypt::encryptString($data->id)]) }}"
                                    class="text-decoration-none">
                                    <div class="mb-2 folder-icon">
                                        <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                    </div>
                                    <h6 class="fs-15 folder-name">{{ $data->name }}</h6>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display Subfolders -->
                @if ($subfolders->count())
                    <div class="col-md-2 col-6 ms-4">
                        @foreach ($subfolders as $subfolder)
                            <div class="card bg-light shadow-none" id="subfolder-{{ $subfolder->id }}">
                                <div class="card-body">
                                    <div class="text-center">
                                        <a href="{{ route('subfolder.show', ['id' => Crypt::encryptString($subfolder->id)]) }}"
                                            class="text-decoration-none">
                                            <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                            <h6 class="fs-15 folder-name">{{ $subfolder->name }}</h6>
                                        </a>
                                        <div class="dropdown">
                                            <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('subfolder.show', ['id' => Crypt::encryptString($subfolder->id)]) }}"><i
                                                            class="bx bx-link me-2"></i> Open Subfolder</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)"
                                                        onclick="confirmDelete('{{ $subfolder->id }}')"><i
                                                            class="bx bx-trash me-2"></i> Delete Subfolder</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach

            {{ $query->links() }}
        @endif
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

    <script>
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
@endsection
