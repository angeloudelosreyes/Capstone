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
                                        <li><a class="dropdown-item" href="{{route('drive.show',['id' => Crypt::encryptString($data->id)])}}" ><i class="bx bx-link me-2"></i> Open File</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="share_file('{{Crypt::encryptString($data->id)}}')"><i class="bx bx-share me-2"></i> Share</a></li>
                                        <li><a class="dropdown-item" href="{{route('drive.download',['id' => Crypt::encryptString($data->id)])}}" ><i class="bx bx-download me-2"></i> Download</a></li>
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
