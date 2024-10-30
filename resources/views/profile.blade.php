@extends('layouts.app')
@section('container')

<div class="row">
    <form action="{{route('account.profile.update')}}" method="POST">
        @csrf
        @honeypot
        <input type="hidden" name="account_id" value="{{Crypt::encryptString(auth()->user()->id)}}">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Update Information</h4>
            </div><!-- end card header -->
            <div class="card-body">
                <div class="row gy-4">
                    <div class="col-12">
                        <div>
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" value="{{auth()->user()->email}}" class="form-control @error('email') is-invalid @enderror"
                                name="email" id="email">
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-12">
                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror"
                                name="password" id="password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6 col-12">
                        <div>
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                name="password_confirmation" id="password_confirmation">
                          @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="mt-3"><button type="submit" class="btn w-100 freeze btn-primary">Save Changes</button></div>

            </div>
        </div>
    </form>

</div>

@endsection
@section('custom_js')
<script>
    $('.profile').addClass('active')
</script>
@endsection