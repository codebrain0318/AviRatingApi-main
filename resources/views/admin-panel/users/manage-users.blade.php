@extends('admin-panel.layout')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark">Manage Users</h1>
			</div><!-- /.col -->
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="{{url('admin')}}">Users</a></li>
					<li class="breadcrumb-item active">Manage Users</li>
				</ol>
			</div>
		</div>
	</div>
</div>

<section class="content">
    <div class="container-fluid">
    	<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">User List</h3>
						<div class="card-tools">
							<div class="input-group input-group-sm" style="width: 150px;">
								<input type="text" name="table_search" class="form-control float-right" placeholder="Search">
								<div class="input-group-append">
									<button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
								</div>
							</div>
						</div>
					</div>
					<!-- /.card-header -->
					<div class="card-body table-responsive p-0" style="height: 300px;">
						<table class="table table-head-fixed">
							<thead>
								<tr>
									<th>ID</th>
									<th>UserName</th>
									<th>Email</th>
									<th>Status</th>
									
								</tr>
							</thead>
							<tbody>
								@foreach($data as $d)
								<tr>
									<td>{{$d->id}}</td>
									<td>{{$d->username}}</td>
									<td>{{$d->email}}</td>
									<td style="width:200px">{{Form::select('status',config('list.user-status'),$d->status, [ 'id'=>'status','data-id'=>$d->id,'class'=>'form-control'])}}</td>
								@endforeach	
								</tr>
							</tbody>
						</table>
					</div>
					<!-- /.card-body -->
				</div>
				<!-- /.card -->
			</div>
		</div>
    </div>
</section>

@endsection