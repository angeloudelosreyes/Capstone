@extends('layouts.app')

@section('container')

    @include('includes.create-sub-folder-modal')

    @include('includes.file-details-modal')


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

        @if (isset($query) &&
                count($query) == 0 &&
                (isset($subfolders) && count($subfolders) == 0) &&
                (isset($files) && count($files) == 0))
            <div class="col-12 mb-3">
                <button class="btn btn-primary" id="createFileButton">Create New File</button>
            </div>
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">{{ $title }}</h5>

            <!-- Add Create Button -->
            <div class="col-12 mb-3 d-flex justify-content-start">
                <button class="btn btn-secondary me-2" onclick="handleBack()">
                    <i class="ri-arrow-left-line"></i> Back
                </button>
            </div>

            <!-- Display Subfolders -->
            @if (isset($subfolders) && count($subfolders) > 0)
                @foreach ($subfolders as $subfolder)
                    <div class="col-md-2 col-6 folder-card">
                        <div class="card bg-light shadow-none">
                            <div class="card-body">
                                <div class="d-flex justify-content-center position-relative">
                                    <a href="{{ route('subfolder.show', ['id' => Crypt::encryptString($subfolder->id)]) }}"
                                        class="text-decoration-none">
                                        <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                    </a>
                                    <!-- Dropdown Menu for Folder Options -->
                                    <div class="dropdown position-absolute" style="top: 5px; right: 5px;">
                                        <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('subfolder.show', ['id' => Crypt::encryptString($subfolder->id)]) }}">
                                                    <i class="bx bx-link me-2"></i> Open Subfolder
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="create_files('{{ Crypt::encryptString($subfolder->id) }}', '{{ $subfolder->name }}')">
                                                    <i class="bx bx-upload me-2"></i> Upload Files
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="update_subfolder('{{ Crypt::encryptString($subfolder->id) }}','{{ $subfolder->name }}')">
                                                    <i class="bx bx-pencil me-2"></i> Rename
                                                </a>
                                            </li>
                                            <li>
                                                <form
                                                    action="{{ route('subfolder.destroy', ['id' => Crypt::encryptString($subfolder->id)]) }}"
                                                    method="POST" style="display: inline;"
                                                    id="delete-form-{{ $subfolder->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="dropdown-item"
                                                        onclick="confirmDelete('{{ $subfolder->id }}')">
                                                        <i class="bx bx-trash me-2"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <h6 class="fs-15 folder-name text-center mt-2">{{ $subfolder->name }}</h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Display Files -->
            @if (isset($files) && count($files) > 0)
                @foreach ($files as $file)
                    <div class="col-md-2 col-6 folder-card">
                        <div class="card bg-light shadow-none">
                            <div class="card-body">
                                <div class="d-flex justify-content-center position-relative">
                                    <a href="javascript:void(0)" class="text-decoration-none"
                                        data-file-id="{{ Crypt::encryptString($file->id) }}"
                                        data-protected="{{ $file->protected }}" data-password="{{ $file->password }}"
                                        onclick="openFile('{{ Crypt::encryptString($file->id) }}', '{{ $file->protected }}', '{{ $file->password }}')">
                                        <div class="mb-2">
                                            @if ($file->extension == 'pdf')
                                                <i class="ri-file-pdf-line align-bottom text-danger display-5"></i>
                                            @elseif ($file->extension == 'xlsx')
                                                <i class="ri-file-excel-fill align-bottom text-success display-5"></i>
                                            @elseif ($file->extension == 'docx')
                                                <i class="ri-file-word-fill align-bottom text-primary display-5"></i>
                                            @else
                                                <i class="ri-file-2-fill align-bottom text-default display-5"></i>
                                            @endif
                                        </div>
                                    </a>
                                    <!-- Dropdown Menu for File Options -->
                                    <div class="dropdown position-absolute" style="top: 5px; right: 5px;">
                                        <button class="btn btn-ghost-primary btn-icon btn-sm dropdown" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-fill fs-16 align-bottom"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item open-file-button" href="javascript:void(0)"
                                                    data-file-id="{{ Crypt::encryptString($file->id) }}"
                                                    data-protected="{{ $file->protected }}"
                                                    data-password="{{ $file->password }}"><i class="bx bx-link me-2"></i>
                                                    Open
                                                    File</a></li>
                                            <li><a class="dropdown-item download-button" href="javascript:void(0)"
                                                    data-file-id="{{ Crypt::encryptString($file->id) }}"><i
                                                        class="bx bx-download me-2"></i> Download</a></li>
                                            <a class="dropdown-item edit-file-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($file->id) }}"
                                                data-protected="{{ $file->protected }}"
                                                data-password="{{ $file->password }}">
                                                <i class="bx bx-edit me-2"></i> Edit
                                            </a>
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="renameFile2('{{ Crypt::encryptString($file->id) }}', '{{ $file->files }}')"><i
                                                        class="bx bx-rename me-2"></i> Rename</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="copyFile('{{ Crypt::encryptString($file->id) }}')"><i
                                                        class="bx bx-copy me-2"></i> Copy</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="moveFile('{{ Crypt::encryptString($file->id) }}')"><i
                                                        class="bx bx-cut me-2"></i> Move</a></li>
                                            <li>
                                                <form
                                                    action="{{ route('shared.destroy', ['id' => Crypt::encryptString($file->id)]) }}"
                                                    method="POST" style="display: inline;"
                                                    id="delete-form-{{ $file->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="dropdown-item"
                                                        onclick="confirmDelete('{{ $file->id }}')">
                                                        <i class="bx bx-trash me-2"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="viewFileDetails('{{ Crypt::encryptString($file->id) }}')">
                                                    <i class="bx bx-info-circle me-2"></i> View Details
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <h6 class="fs-15 folder-name text-center mt-2">{{ $file->files }}</h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif


            <!-- Display Existing Folder Contents -->
            @if (isset($query) && count($query) > 0)
                @foreach ($query as $data)
                    <div class="col-md <div class="col-6 folder-card">
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
                                                        class="bx bx-link me-2"></i> Open File</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="share_file('{{ Crypt::encryptString($data->id) }}')"><i
                                                        class="bx bx-share me-2"></i> Share</a></li>
                                            <li><a class="dropdown-item download-button" href="javascript:void(0)"
                                                    data-file-id="{{ Crypt::encryptString($data->id) }}"><i
                                                        class="bx bx-download me-2"></i> Download</a></li>
                                            <a class="dropdown-item edit-file-button" href="javascript:void(0)"
                                                data-file-id="{{ Crypt::encryptString($data->id) }}"
                                                data-protected="{{ $data->protected }}"
                                                data-password="{{ $data->password }}">
                                                <i class="bx bx-edit me-2"></i> Edit
                                            </a>
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="renameFile('{{ Crypt::encryptString($data->id) }}', '{{ $data->files }}')"><i
                                                        class="bx bx-rename me-2"></i> Rename</a></li>
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
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <a href="{{ route('drive.show', ['id' => Crypt::encryptString($data->id)]) }}"
                                        class="text-decoration-none">
                                        <div class="mb-2">
                                            @if ($data->extension == 'txt')
                                                <i class="ri-file-2-fill align-bottom text-default display-5"></i>
                                            @elseif($data->extension == 'pdf')
                                                <i class="ri-file-pdf-line align-bottom text-danger display-5"></i>
                                            @elseif($data->extension == 'docx')
                                                <i class="ri-file-word-fill align-bottom text-primary display-5"></i>
                                            @elseif($data->extension == 'xlsx')
                                                <i class="ri-file-excel-fill align-bottom text-success display-5"></i>
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
                {{ $files->links() }}
            @endif

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
            console.log("promptForPassword called with action:", action);

            if (isProtected === 'YES' && hasPassword) {
                console.log("Showing password modal for protected file with action:", action);
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

                    console.log("Navigating to:", targetUrl);
                    window.location.href = targetUrl;
                });
            } else {
                // Directly proceed to the target URL based on the action if no password is required
                console.log("Directly navigating to target URL without password modal for action:", action);
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
@endsection
<script>
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

@section('custom_js')
    <script>
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
        function handleBack() {
            window.history.back(); // This will take the user to the previous page in the browser history
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
