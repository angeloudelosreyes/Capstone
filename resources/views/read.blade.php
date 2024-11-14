@extends('layouts.app')

@section('container')
    <div class="row">
        <h5 class="mb-4 text-uppercase fw-bolder">{{ $title }}</h5>
        <div class="col-12">
            @if ($extension == 'txt')
                {!! $content !!}
            @elseif($extension == 'pdf')
                <div class="mb-4" style="overflow:hidden;height:800px">
                    <iframe src="{{ route('drive.pdf.display', ['title' => $title, 'content' => Crypt::encryptString($content)]) }}" width="100%" height="100%"></iframe>
                </div>
            @elseif($extension == 'docx')
                <div class="mb-4">
                    {!! $content !!} <!-- Display DOCX content as HTML -->
                </div>
            @else
                <p>Unsupported file type.</p>
            @endif
        </div>
    </div>
@endsection

@section('custom_js')
    <!-- Example of adding custom JS -->
    <script>
        console.log("Read Blade is loaded.");
    </script>
@endsection

