<form action="{{route('account.store')}}" method="POST">
    @csrf
    @honeypot
    <div class="modal component fade" id="create_account" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-4">
                        <div class="col-md-6 col-12">
                            <div>
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" >
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div>
                                <label for="department" class="form-label">Department</label>
                                <select name="department" class="form-control">
                                    <option value="CCS">CCS</option>
                                    <option value="CTE">CTE</option>
                                    <option value="CBE">CBE</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div>
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" >
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div>
                                <label for="age" class="form-label">Age</label>
                                <input type="text" class="form-control @error('age') is-invalid @enderror" name="age" >
                                @error('age')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div>
                                <label for="address" class="form-label">Address</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" cols="30" rows="10"></textarea>
                                @error('address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Create</button>
                </div>
            </div>
        </div>
    </div>
</form>
