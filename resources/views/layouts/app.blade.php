<!doctype html>
<html lang="en" data-layout="horizontal" data-bs-theme="light" data-topbar="dark" data-sidebar-size="lg"
    data-sidebar="dark">
@include('layouts.header')

<body>
    <div id="layout-wrapper">
        @include('layouts.topbar')
        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-3 pb-1">
                        <div class="col-12">
                            <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                                <div class="flex-grow-1">
                                    <h4 class="fs-16 mb-1">Welcome Back!
                                        {{ auth()->user()->name }}</h4>
                                    <p class="text-muted mb-0">Here's what's happening with your portal today.</p>
                                </div>

                                @if (request()->is('home') || request()->is('folder/show/*'))
                                    <div class="mt-3 text-uppercase fs-5 mt-lg-0">
                                        <button onclick="createFolderModal()" class="btn btn-primary text-uppercase">
                                            <i class="bx bx-folder-plus fs-3 align-middle me-2"></i> Create Folder
                                        </button>
                                    </div>
                                @elseif (request()->is('shared'))
                                    <div class="mt-3 text-uppercase fs-5 mt-lg-0">
                                        <button onclick="createShareableFolderModal()"
                                            class="btn btn-primary text-uppercase">
                                            <i class="bx bx-folder-plus fs-3 align-middle me-2"></i> Create Shareable
                                            Folder
                                        </button>

                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @yield('container')
                </div>
            </div>

            @include('layouts.footer')
        </div>
    </div>

    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>

    @include('includes.create-folder-modal')
    @include('includes.create-folder-shareable-modal')
    @include('includes.update-folder-modal')
    @include('includes.upload-files-modal')
    @include('includes.update-subfolder-modal')
    @include('includes.share-modal')
    @include('includes.create-user-modal')
    @include('includes.update-user-modal')
    @include('includes.upload-subfiles-modal')
    @include('includes.move-file-modal')
    @include('includes.copy-file-modal')
    @include('includes.file-details-modal')
    @include('includes.folder-share-modal')

    @include('layouts.scripts')
    @yield('custom_js')

</body>

</html>
