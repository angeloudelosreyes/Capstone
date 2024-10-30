@extends('layouts.app')
@section('custom_css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
<!--datatable responsive css-->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="btn-group">
            <a onclick="create_account()" class="btn rounded-0 text-uppercase btn-primary"><i
                    class="bx bx-plus align-middle me-2"></i>Create</a>
        </div>
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1 text-uppercase fs-13">All Users</h4>
            </div>
            <div class="card-body">
                <div class="table-card">
                    <table id="model-datatables"
                        class="table table-hover table-borderless table-centered align-middle table-nowrap mb-0"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:1px">Action</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Email Address</th>
                                <th>Address</th>
                                <th>Age</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($query as $data)
                            <tr>
                                <td style="width:1px" class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-primary btn-sm dropdown" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start">
                                            @if(Route::has('account.update'))
                                            <li>
                                                <a href="javascript:void(0)" onclick="account_update('{{Crypt::encryptString($data->id)}}','{{$data->name}}','{{$data->department}}','{{$data->email}}','{{$data->address}}','{{$data->age}}')" class="dropdown-item">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                    Edit
                                                </a>
                                            </li>

                                            <li>
                                                <a href="{{route('account.destroy',['id' => Crypt::encryptString($data->id)])}}" class="dropdown-item">
                                                    <i class="bx bx-trash align-bottom me-2 text-muted"></i>Delete
                                                </a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>{{$data->name}}</td>
                                <td>{{$data->department}}</td>
                                <td>{{$data->email}}</td>
                                <td>{{$data->address}}</td>
                                <td>{{$data->age}}</td>
                                <td>{{$data->created_at}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('custom_js')
<script>
    $('.account').addClass('active')

</script>


<!--datatable js-->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        new DataTable("#model-datatables", {
            paging: false,
            ordering: false,
            bInfo: false,
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (e) {
                            e = e.data();
                            return "Details for " + e[0] + " " + e[1]
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                        tableClass: "table"
                    })
                }
            }
        })
    })

</script>

@endsection
