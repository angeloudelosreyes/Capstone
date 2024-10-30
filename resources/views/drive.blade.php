@extends('layouts.app')
@section('container')
    <div class="row">
        @if(count($query) == 0)
            <div class="col-12 mb-3">
                <button class="btn btn-primary" id="createFileButton">Create New File</button>
            </div>
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">{{$title}}</h5>

            <!-- Add Create Button -->
            <div class="col-12 mb-3">
                <button class="btn btn-primary" id="createFileButton">Create New File</button>
            </div>
            @foreach($query as $data)
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
                                        <li><a class="dropdown-item" href="{{route('drive.show',['id' => Crypt::encryptString($data->id)])}}" ><i class="bx bx-link me-2"></i> Open File</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="share_file('{{Crypt::encryptString($data->id)}}')"><i class="bx bx-share me-2"></i> Share</a></li>
                                        <li><a class="dropdown-item download-button" href="javascript:void(0)" data-file-id="{{ Crypt::encryptString($data->id) }}"><i class="bx bx-download me-2"></i> Download</a></li>
                                        <li><a class="dropdown-item" href="{{route('drive.edit',['id' => Crypt::encryptString($data->id)])}}"><i class="bx bx-edit me-2"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="{{route('drive.destroy',['id' => Crypt::encryptString($data->id)])}}"><i class="bx bx-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="mb-2">
                                    @if($data->extension == 'txt')
                                        <i class="ri-file-2-fill align-bottom text-default display-5"></i>
                                    @elseif($data->extension == 'pdf')
                                        <i class="ri-file-pdf-line align-bottom text-danger display-5"></i>
                                    @elseif($data->extension == 'docx')
                                        <i class="ri-file-word-fill align-bottom text-success display-5"></i>
                                    @else
                                        <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                                    @endif
                                </div>
                                <h6 style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;"class="fs-15 folder-name">{{$data->files}}</h6>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
            {{$query->links()}}
        @endif
    </div>
@endsection

@section('custom_js')
    <script>
        document.getElementById('createFileButton').addEventListener('click', function() {
            const folderId = '{{ $folderId }}'; // Pass the folder_id from the Blade template

            Swal.fire({
                title: '<strong>Create New File</strong>',
                icon: 'folder-plus',
                html: `
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px;">
                <div style="display: flex; align-items: center; width: 100%;">
                    <label for="fileName" style="flex-basis: 30%; text-align: left;">File Name:</label>
                    <input type="text" id="fileName" class="swal2-input" placeholder="Enter the file name" style="flex-basis: 70%;">
                </div>
                <div style="display: flex; align-items: center; width: 100%;">
                    <label for="fileType" style="flex-basis: 30%; text-align: left;">File Type:</label>
                    <select id="fileType" class="swal2-input" style="flex-basis: 70%;">
                        <option value="docx">Word File (.docx)</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; width: 100%;">
                    <label for="isProtected" style="flex-basis: 30%; text-align: left;">Protected:</label>
                    <input type="checkbox" id="isProtected" class="swal2-checkbox" style="flex-basis: 70%;">
                </div>
                <div style="display: flex; align-items: center; width: 100%;" id="passwordField" hidden>
                    <label for="password" style="flex-basis: 30%; text-align: left;">Password:</label>
                    <input type="password" id="password" class="swal2-input" placeholder="Enter the password" style="flex-basis: 70%;">
                </div>
            </div>
            <input type="hidden" id="folderId" value="${folderId}">
        `,
                showCancelButton: true,
                confirmButtonText: 'Create',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const fileName = document.getElementById('fileName').value;
                    const fileType = document.getElementById('fileType').value;
                    const folderId = document.getElementById('folderId').value;
                    const isProtected = document.getElementById('isProtected').checked;
                    const password = document.getElementById('password').value;
                    const requestBody = { fileName: fileName, fileType: fileType, folder_id: folderId, isProtected: isProtected, password: isProtected ? password : null };

                    if (!fileName.trim()) {
                        Swal.showValidationMessage('Please enter a file name.');
                        return;
                    }

                    if (isProtected && !password.trim()) {
                        Swal.showValidationMessage('Please enter a password.');
                        return;
                    }

                    return fetch('{{ route('files.create') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(requestBody)
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText);
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'File Created',
                        text: 'Your file has been created successfully.'
                    }).then(() => {
                        location.reload();
                    });
                }
            });

            document.getElementById('isProtected').addEventListener('change', function() {
                const passwordField = document.getElementById('passwordField');
                if (this.checked) {
                    passwordField.hidden = false;
                } else {
                    passwordField.hidden = true;
                }
            });
        });

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

                    return fetch(`{{ url('drive/download') }}/${fileId}?password=${encodeURIComponent(password)}`, {
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
