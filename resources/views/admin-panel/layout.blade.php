<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<link rel="icon" type="image/png" href="{{asset('images/favicon.png')}}">
		<title>Property Hunt AP</title>
		<!-- Tell the browser to be responsive to screen width -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- Font Awesome -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/fontawesome-free/css/all.min.css')}}">
		<!-- Ionicons -->
		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
		<!-- Tempusdominus Bbootstrap 4 -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
		<!-- iCheck -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
		<!-- JQVMap -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/jqvmap/jqvmap.min.css')}}">
		<!-- Theme style -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/dist/css/adminlte.min.css')}}">
		<!-- overlayScrollbars -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
		<!-- Daterange picker -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/daterangepicker/daterangepicker.css')}}">
		<!-- summernote -->
		<link rel="stylesheet" href="{{url('admin-panel/AdminLTE-3.0.1/plugins/summernote/summernote-bs4.css')}}">
		<!-- Google Font: Source Sans Pro -->
		<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
	</head>
	
	<body class="hold-transition layout-fixed"> <!-- sidebar-mini -->
		<div class="wrapper">
			<!-- Main Sidebar Container -->
			<aside class="main-sidebar sidebar-dark-primary elevation-4">
				<!-- Brand Logo -->
				<a href="index3.html" class="brand-link">
					<img src="{{url('admin-panel/AdminLTE-3.0.1/dist/img/AdminLTELogo.png')}}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
					style="opacity: .8">
					<span class="brand-text font-weight-light">PropertyHunt</span>
				</a>
				<!-- Sidebar nav-compact nav-flat -->
				<div class="sidebar nav-flat"> 
					<!-- Sidebar user panel (optional) 
					<div class="user-panel mt-3 pb-3 mb-3 d-flex">
						<div class="image">
							<img src="{{url('admin-panel/AdminLTE-3.0.1/dist/img/user2-160x160.jpg')}}" class="img-circle elevation-2" alt="User Image">
						</div>
						<div class="info">
							<a href="#" class="d-block">Alexander Pierce</a>
						</div>
					</div>
					-->
					<!-- Sidebar Menu -->
					<nav class="mt-2">
						@include('admin-panel.menu')
					</nav>
					<!-- /.sidebar-menu -->
				</div>
				<!-- /.sidebar -->
			</aside>
			<!-- Content Wrapper. Contains page content -->
			<div class="content-wrapper">
				@yield('content')
			</div>
			
			<!-- /.content-wrapper -->
			<footer class="main-footer">
			    <strong>Copyright &copy; 2019-2020 <a href="http://https://www.propertyhuntsa.co.za/">PropertyHunt</a>.</strong>
			    All rights reserved.
			    <div class="float-right d-none d-sm-inline-block">
			        <b>Version</b> 3.0.1
			    </div>
			</footer>
			<!-- Control Sidebar -->
			<aside class="control-sidebar control-sidebar-dark">
			    <!-- Control sidebar content goes here -->
			</aside>
				<!-- /.control-sidebar -->
		</div>
	<!-- Scripts -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/jquery/jquery.min.js')}}"></script>
		<!-- jQuery UI 1.11.4 -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
		<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
		<script>
		$.widget.bridge('uibutton', $.ui.button)
		</script>
		<!-- Bootstrap 4 -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
		<!-- ChartJS -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/chart.js/Chart.min.js')}}"></script>
		<!-- Sparkline -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/sparklines/sparkline.js')}}"></script>
		<!-- JQVMap -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/jqvmap/jquery.vmap.min.js')}}"></script>
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
		<!-- jQuery Knob Chart -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/jquery-knob/jquery.knob.min.js')}}"></script>
		<!-- daterangepicker -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/moment/moment.min.js')}}"></script>
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/daterangepicker/daterangepicker.js')}}"></script>
		<!-- Tempusdominus Bootstrap 4 -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
		<!-- Summernote -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/summernote/summernote-bs4.min.js')}}"></script>
		<!-- overlayScrollbars -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
		<!-- AdminLTE App -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/dist/js/adminlte.js')}}"></script>
		<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/dist/js/pages/dashboard.js')}}"></script>

		<script src="{{ url('js/application.js')}}"></script>

		<!-- AdminLTE for demo purposes -->
		<script src="{{ url('admin-panel/AdminLTE-3.0.1/dist/js/demo.js')}}"></script>
		<!-- manage user js -->
		<script src="{{ url('admin-panel/admin/user.js')}}"></script>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
		<script src="https://cdn.jsdelivr.net/npm/promise-polyfill"></script>


	</body>
</html>