@include('layouts.header')

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

@include('layouts.navbar')

@include('layouts.sidebar')

@yield('content')

@include('layouts.footer')
