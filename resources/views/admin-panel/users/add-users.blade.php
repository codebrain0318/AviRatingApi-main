@extends('admin-panel.layout')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark">Add User</h1>
			</div><!-- /.col -->
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="{{url('admin')}}">Users</a></li>
					<li class="breadcrumb-item active">Add User</li>
				</ol>
			</div>
		</div>
	</div>
</div>

<section class="content">
    <div class="container-fluid">
    	<div class="card">
	      <!-- <div class="card-header">
	        <h3 class="card-title">Quick Example</h3>
	      </div> -->
	      <!-- /.card-header -->
	      <!-- form start -->
	     	{{Form::model($data, ['id'=>'blog-category-form', 'files'=>true, 'url'=>'admin/save-blog-category'])}}
            {{Form::hidden('id')}}
	        <div class="card-body">
	          <div class="form-group">
	            <label for="exampleInputEmail1">Email address</label>
	            <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Enter email">
	          </div>
	          <div class="form-group">
	            <label for="exampleInputPassword1">Password</label>
	          	 {{Form::text('text',null,['class'=>'form-control','placeholder'=>'Password',])}}
	          </div>
	        </div>
	        <!-- /.card-body -->

	        <div class="card-footer">
	          <button type="submit" class="btn btn-primary">Submit</button>
	        </div>
	      {{Form::close()}}
	    </div>
    </div>
</section>

@endsection