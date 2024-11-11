@extends('layouts.app')
@section('container')
    <div class="row">
        <!-- Display created shareable folders -->
        @if ($createdFolders->isEmpty() && $sharedFiles->isEmpty())
            <div class="col-12">
                <div class="alert alert-warning">You haven't created any folders yet.</div>
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
                                <a href="{{ route('folder.shareable.show', ['id' => Crypt::encryptString($folder->id)]) }}"
                                    class="text-decoration-none">
                                    <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                </a>
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
                                        <li><a class="dropdown-item"
                                                href="{{ route('shared.edit', ['id' => Crypt::encryptString($data->id)]) }}"><i
                                                    class="bx bx-edit me-2"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="renameFile2('{{ Crypt::encryptString($data->id) }}', '{{ $data->files }}')"><i
                                                    class="bx bx-rename me-2"></i> Rename</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="copyFile('{{ Crypt::encryptString($data->id) }}')"><i
                                                    class="bx bx-copy me-2"></i> Copy</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)"
                                                onclick="moveFile('{{ Crypt::encryptString($data->id) }}')"><i
                                                    class="bx bx-cut me-2"></i> Move</a></li>
                                        <li>
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
                                <a href="javascript:void(0)" class="text-decoration-none"
                                    data-file-id="{{ Crypt::encryptString($data->id) }}"
                                    data-protected="{{ $data->protected }}" data-password="{{ $data->password }}"
                                    onclick="promptForPassword('{{ Crypt::encryptString($data->id) }}', '{{ $data->protected }}', '{{ $data->password }}', 'open')">
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
                                </a>
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
@endsection
@section('custom_js')
    <script>
        function promptForPassword(fileId, isProtected, hasPassword, action) {
            if (isProtected === 'YES' && hasPassword) {
                // Show the password modal
                $('#passwordModal').modal('show');

                // Configure the submit button to handle the password for either open or edit action
                $('#submitPassword').off('click').on('click', function() {
                    const password = $('#filePassword').val();
                    if (!password) {
                        alert('Password is required');
                        return;
                    }

                    // Determine the target URL based on the action (either open or edit)
                    let targetUrl = (action === 'edit') ?
                        `{{ url('shared/edit') }}/${fileId}?password=${encodeURIComponent(password)}` :
                        `{{ url('drive/sharedShow') }}/${fileId}?password=${encodeURIComponent(password)}`;

                    window.location.href = targetUrl;
                });
            } else {
                // Directly proceed to the target URL based on the action if no password is required
                let targetUrl = (action === 'edit') ?
                    `{{ url('shared/edit') }}/${fileId}` :
                    `{{ url('drive/sharedShow') }}/${fileId}`;

                window.location.href = targetUrl;
            }
        }

        // Handle click events for both open and edit buttons
        document.querySelectorAll('.open-file-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                const isProtected = this.getAttribute('data-protected');
                const hasPassword = this.getAttribute('data-password') !== '';
                promptForPassword(fileId, isProtected, hasPassword, 'open');
            });
        });

        document.querySelectorAll('.edit-file-button').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                const isProtected = this.getAttribute('data-protected');
                const hasPassword = this.getAttribute('data-password') !== '';
                promptForPassword(fileId, isProtected, hasPassword, 'edit');
            });
        });

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}'
            });
        @endif
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

                    return fetch(`{{ url('shared') }}/${fileId}/download`, {
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
                            a.download = 'protected-file.zip';
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
    <script>
        function renameFile2(fileId, oldName) {
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
                    return fetch(`{{ route('shared.rename', '') }}/${fileId}`, {
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
                        .catch(() => {
                            Swal.fire('Renamed!', 'File has been renamed successfully.', 'success').then(
                                () => {
                                    location.reload(); // Reload the page after successful rename
                                });

                        });
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
        function moveFile(fileId) {
            // Set the hidden input for fileId
            document.getElementById('fileIdToMove').value = fileId;

            const destinationFolderSelect = document.getElementById('destinationFolder');
            destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

            // Fetch the available folders from the shared folders route
            fetch("{{ route('shared.getSharedFolders') }}")
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
                const actionUrl = `{{ url('shared/move') }}/${fileId}/${destinationFolderId}`;
                console.log("Form action URL set to:", actionUrl); // Log to verify
                document.getElementById('moveFileForm').action = actionUrl;
            }
        });

        function submitMoveFileForm() {
            const fileId = document.getElementById('fileIdToMove').value;
            const destinationFolderId = document.getElementById('destinationFolder').value;

            if (fileId && destinationFolderId) {
                // Construct the route URL with the parameters
                const actionUrl = `{{ url('shared/move') }}/${fileId}/${destinationFolderId}`;
                document.getElementById('moveFileForm').action = actionUrl; // Set the form action

                console.log("Form action URL set to:", actionUrl); // Log to verify
                document.getElementById('moveFileForm').submit(); // Submit the form
            } else {
                Swal.fire('Error', 'Please select a destination folder.', 'error');
            }
        }
    </script>
    <script>
        // Copy a file
        function copyFile(fileId) {
            console.log("Copying file with ID:", fileId);

            // Set the hidden input for fileId in the form
            document.getElementById('fileIdToCopy').value = fileId;

            const destinationFolderSelect = document.getElementById('copyDestinationFolder');
            destinationFolderSelect.innerHTML = '<option value="">Loading folders...</option>';

            // Fetch the available folders to populate the dropdown
            fetch("{{ route('shared.getSharedFolders') }}")
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
                // Set the form action to the shared.paste route
                form.action = `{{ route('shared.paste', '') }}/${destinationFolderId}`;
                form.method = 'POST'; // Ensure it's a POST request
                form.submit(); // Submit the form to paste the copied file
            } else {
                Swal.fire('Error', 'Please select a destination folder.', 'error');
            }
        }
    </script>
@endsection
