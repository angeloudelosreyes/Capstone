
<!doctype html>
<html lang="en" data-layout="horizontal" data-topbar="dark" data-sidebar-size="lg" data-sidebar="light" data-sidebar-image="none" data-preloader="disable">

@include('layouts.header')

<body>

    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
        <div class="bg-overlay" ></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="javascript:void(0)" class="d-inline-block auth-logo">
                                    <h1 class="text-white">Web-Based File Management and File Security System</h1>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->
                @error('invalid')
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="alert text-center alert-danger">
                            <strong>{{ $message }}</strong>
                        </div>
                    </div>
                </div>
                @enderror

                <div class="row justify-content-center">
                    <div class="col-md-5 col-12">

                        <div class="card mt-4">
                        

                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Welcome Back !</h5>
                                    <p class="text-muted">Sign in to continue.</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <form method="POST" action="{{ route('login') }}">
                                        @csrf
                                        @honeypot

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" id="email" >
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            @if (Route::has('password.request'))
                                                <div class="float-end">
                                                    <a href="{{ route('password.request') }}" class="text-muted">Forgot password?</a>
                                                </div>
                                            @endif
                                            
                                            <label class="form-label" for="password-input">Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror pe-5 password-input" name="password" id="password-input">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                            </div>
                                            
                                        </div>

                                        <div class="form-check">
                                           <input class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} id="remember">
                                           <label class="form-check-label" for="remember">Remember me</label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-primary w-100" type="submit">Sign In</button>
                                        </div>

                                        
                                    </form>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy;
                                <script>document.write(new Date().getFullYear())</script> {{config('app.name')}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- JAVASCRIPT -->
    @include('layouts.scripts')
    <script src="{{asset('storage/libs/particles.js/particles.js')}}"></script>
    <script src="{{asset('storage/js/pages/particles.app.js')}}"></script>
    <script src="{{asset('storage/js/pages/password-addon.init.js')}}"></script>
</body>

</html>