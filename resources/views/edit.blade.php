@extends('layouts.app')
@section('container')
    <div class="row">
        <h5 class="mb-4 text-uppercase fw-bolder">Edit {{ $query->files }}</h5>

        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="col-12">
            <form id="editForm" action="{{ route('drive.update', ['id' => Crypt::encryptString($query->id)]) }}"
                method="POST" enctype="multipart/form-data" onsubmit="fetchTinyMCEContent()">
                @csrf
                @method('POST')

                @if ($extension == 'txt' || $extension == 'docx')
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="20">{!! $content !!}</textarea>
                    </div>
                @elseif($extension == 'pdf')
                    <div class="mb-3">
                        <label for="pdf_file" class="form-label">Upload a New PDF to Replace</label>
                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf">
                    </div>
                    <p>Current PDF cannot be edited directly. Upload a new PDF file to replace it.</p>

                    <!-- Display current PDF in an iframe for preview -->
                    <div class="mb-4" style="overflow:hidden; height:800px;">
                        <iframe
                            src="{{ route('drive.pdf.display', ['title' => $query->files, 'content' => Crypt::encryptString($query->file_path)]) }}"
                            width="100%" height="100%" style="border: none;"></iframe>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@endsection

@section('custom_js')
    @if ($extension == 'txt' || $extension == 'docx')
        <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/5/tinymce.min.js" referrerpolicy="origin">
        </script>
        <script>
            tinymce.init({
                selector: '#content',
                plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                toolbar_mode: 'floating',
            });

            function fetchTinyMCEContent() {
                const content = tinymce.get('content').getContent();
                document.getElementById('content').value = content;
                console.log(content);
            }
        </script>
    @endif
@endsection
