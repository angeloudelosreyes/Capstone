<head>

    <meta charset="utf-8" />
    <title>{{config('app.name')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{asset('storage/images/favicon.ico')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Layout config Js -->
    <script src="{{asset('storage/js/layout.js')}}"></script>
    <!-- Bootstrap Css -->
    <link href="{{asset('storage/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{asset('storage/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{asset('storage/css/app.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{asset('storage/css/custom.min.css')}}" rel="stylesheet" type="text/css" />

    <link href="{{asset('storage/FreezeUi/freeze-ui.min.css')}}" rel="stylesheet" type="text/css" />
    @yield('custom_css')
</head>