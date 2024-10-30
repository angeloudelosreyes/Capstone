@extends('layouts.app')
@section('container')
    <div class="row">
        @if(count($query) == 0)
            <div class="col-12">
                <div class="alert alert-warning">You haven't created a folder yet.</div>
            </div>
        @else
            <h5 class="mb-4 text-uppercase fw-bolder">{{$title}}</h5>

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
                                        <li><a class="dropdown-item open-file-button" href="javascript:void(0)" data-file-id="{{ Crypt::encryptString($data->id) }}" data-protected="{{ $data->protected }}" data-password="{{ $data->password }}"><i class="bx bx-link me-2"></i> Open File</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="share_file('{{Crypt::encryptString($data->id)}}')"><i class="bx bx-share me-2"></i> Share</a></li>
                                        <li><a class="dropdown-item download-button" href="javascript:void(0)" data-file-id="{{ Crypt::encryptString($data->id) }}" data-protected="{{ $data->protected }}" data-password="{{ $data->password }}"><i class="bx bx-download me-2"></i> Download</a></li>
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

        function openFile(fileId, isProtected, hasPassword) {
            if (isProtected === 'YES' && hasPassword) {
                $('#passwordModal').modal('show');
                $('#submitPassword').off('click').on('click', function() {
                    const password = $('#filePassword').val();
                    if (!password) {
                        alert('Password is required');
                        return;
                    }
                    window.location.href = `{{ url('drive/sharedShow') }}/${fileId}?password=${encodeURIComponent(password)}`;
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

        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}'
        });
        @endif
    </script>
@endsection
