@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Folders</h2>

        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif


        @if ($folders->isEmpty())
            <p>No folders found.</p>
        @else
            <div class="list-group">
                @foreach ($folders as $folder)
                    <div class="list-group-item">
                        <h4>{{ $folder->name }}</h4>
                        <small>Created at: {{ $folder->created_at->format('d M Y') }}</small>

                        @if ($folder->subfolders->isEmpty())
                            <p>No subfolders found within this folder.</p>
                        @else
                            <div class="ml-4">
                                <h5>Subfolders:</h5>
                                <ul class="list-group">
                                    @foreach ($folder->subfolders as $subfolder)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6>{{ $subfolder->name }}</h6>
                                                <small>Created at: {{ $subfolder->created_at->format('d M Y') }}</small>
                                            </div>
                                            <div>
                                                <a href="{{ route('subfolder.show', $subfolder->id) }}"
                                                    class="btn btn-primary btn-sm">View</a>
                                                <button class="btn btn-secondary btn-sm"
                                                    onclick="renameSubfolder({{ $subfolder->id }})">Rename</button>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="deleteSubfolder({{ $subfolder->id }})">Delete</button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4">
            <form action="{{ route('subfolder.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="title">Create New Subfolder</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Subfolder name"
                        required>
                    <input type="hidden" name="parent_id" value="{{ $parentFolderId }}">
                </div>
                <button type="submit" class="btn btn-success mt-2">Create Subfolder</button>
            </form>
        </div>
    </div>

    <script>
        function renameSubfolder(id) {
            // Add rename functionality (e.g., open a modal or inline editing)
        }

        function deleteSubfolder(id) {
            if (confirm('Are you sure you want to delete this subfolder?')) {
                // Add delete functionality (e.g., AJAX or form submission)
            }
        }
    </script>
@endsection
