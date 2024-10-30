@extends('layouts.app')
@section('container')
    <div class="row">
        <h5 class="mb-4 text-uppercase fw-bolder">Edit {{$query->files}}</h5>
        @if(session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="col-12">
            <form id="editForm" action="{{ route('drive.update', ['id' => Crypt::encryptString($query->id)]) }}" method="POST" onsubmit="fetchTinyMCEContent()">
                @csrf
                @method('POST')
                @if($extension == 'txt' || $extension == 'docx')
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="20">{!! $content !!}</textarea>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@endsection

@section('custom_js')
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
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
@endsection
