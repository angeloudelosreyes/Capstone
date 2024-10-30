<!doctype html>
<html lang="en" data-layout="horizontal" data-topbar="dark" data-sidebar-size="lg" data-sidebar="light" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>{{config('app.name')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{asset('storage/images/mini-logos.png')}}">
    <script src="{{asset('storage/js/layout.js')}}"></script>
    <link href="{{asset('storage/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('storage/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('storage/css/app.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('storage/css/custom.min.css')}}" rel="stylesheet" type="text/css" />
</head>

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
                                    <img src="{{asset('storage/images/logo-light.png')}}" width="120">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->
                @if (session('status'))
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="alert text-center alert-success">
                            <strong>{{ session('status') }}</strong>
                        </div>
                    </div>
                </div>
               @endif
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Create new password</h5>
                                    <p class="text-muted">Your new password must be different from previous used password.</p>
                                </div>
                                <div class="p-2">
                                    <form method="POST" action="{{ route('password.update') }}">
                                        @csrf
                                        @honeypot
                                        <input type="hidden" name="token" value="{{ $token }}">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">{{ __('Email') }}</label>
                                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter email" name="email">
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                             <label class="form-label" for="password-input">{{ __('Password') }}</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror pe-5 password-input" placeholder="Enter password" name="password" autocomplete="new-password">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label" for="confirm-password-input">{{ __('Confirm Password') }}</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input id="password-confirm" type="password" class="form-control pe-5 password-input" placeholder="Confirm password" name="password_confirmation" autocomplete="new-password">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button class="btn btn-danger w-100" type="submit">{{ __('Reset Password') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                        <div class="mt-4 text-center">
                            <p class="mb-0">Wait, I remember my password... <a href="{{url('login')}}" class="fw-semibold text-danger text-decoration-underline"> Click here </a> </p>
                        </div>
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
    <script src="{{asset('storage/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('storage/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{asset('storage/libs/node-waves/waves.min.js')}}"></script>
    <script src="{{asset('storage/libs/feather-icons/feather.min.js')}}"></script>
    <script src="{{asset('storage/js/pages/plugins/lord-icon-2.1.0.js')}}"></script>
    <script src="{{asset('storage/js/plugins.js')}}"></script>
    <script src="{{asset('storage/libs/particles.js/particles.js')}}"></script>
    <script src="{{asset('storage/js/pages/particles.app.js')}}"></script>
    <script src="{{asset('storage/js/pages/password-addon.init.js')}}"></script>
</body>

</html>