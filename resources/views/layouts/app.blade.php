<!doctype html>
<html lang="en" data-layout="horizontal" data-bs-theme="light"  data-topbar="dark" data-sidebar-size="lg" data-sidebar="dark">
@include('layouts.header')

<body>
<!-- <body style="background:#eee"> -->

    <!-- Begin page -->
    <div id="layout-wrapper">


        <!-- ========== App Menu ========== -->
        @include('layouts.topbar')
        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->

                    <div class="row mb-3 pb-1">
                        <div class="col-12">
                            <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                                <div class="flex-grow-1">
                                    <h4 class="fs-16 mb-1">Welcome Back!
                                        {{auth()->user()->name}}</h4>
                                    <p class="text-muted mb-0">Here's what's happening with your portal today.</p>
                                </div>
                                @if(request()->is('home'))
                                    <div class="mt-3 text-uppercase fs-5 mt-lg-0">
                                        <button onclick="createFolderModal()" class="btn btn-primary text-uppercase">
                                            <i class="bx bx-folder-plus fs-3 align-middle me-2"></i> Create Folder
                                        </button>
                                    </div>
                                @endif

                            </div><!-- end card header -->
                        </div>

                        <!--end col-->
                    </div>

                    @yield('container')
                    <!-- end page title -->

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('layouts.footer')
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!-- JAVASCRIPT -->


    @include('includes.create-folder-modal')
    @include('includes.update-folder-modal')
    @include('includes.upload-files-modal')
    @include('includes.share-modal')
    @include('includes.create-user-modal')
    @include('includes.update-user-modal')

    @include('layouts.scripts')
    @yield('custom_js')
</body>

</html>
