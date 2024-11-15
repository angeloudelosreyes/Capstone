<div class="col-md-2 col-6 folder-card">
    <div class="card bg-light shadow-none">
        <div class="card-body">
            <div class="d-flex justify-content-center position-relative">
                <a href="{{ route('subfolder.show', ['id' => Crypt::encryptString($subfolder->id)]) }}"
                    class="text-decoration-none">
                    <i class="ri-folder-2-fill align-bottom text-warning display-5"></i>
                </a>
            </div>
            <h6 class="fs-15 folder-name text-center mt-2">{{ $subfolder->name }}</h6>
            @if ($subfolder->subfolders->isNotEmpty())
                <div class="nested-subfolders">
                    @foreach ($subfolder->subfolders as $nestedSubfolder)
                        @include('partials.subfolder', ['subfolder' => $nestedSubfolder])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
