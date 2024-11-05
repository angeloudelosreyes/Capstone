<form action="{{ route('folder.store') }}" method="POST">
    @csrf
    @honeypot
    <div class="modal component fade @if ($errors->any()) show @endif" id="create_folder" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true"
        @if ($errors->any()) style="display: block;" @endif>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="title" class="form-label">Folder Name</label>
                                <input type="text" class="form-control" name="title" id="title"
                                    value="{{ old('title') }}">
                                @error('title')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Create</button>
                </div>
            </div>
        </div>
    </div>
</form>

@section('custom_js')
    <script>
        // Check if there are any validation errors and open the modal if there are
        @if ($errors->any())
            $(document).ready(function() {
                $('#create_folder').modal('show');
            });
        @endif
    </script>
@endsection
