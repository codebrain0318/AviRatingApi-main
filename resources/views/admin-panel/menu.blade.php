<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
	<!-- Add icons to the links using the .nav-icon class
	with font-awesome or any other icon font library 
	<li class="nav-item has-treeview menu-open">
		<a href="#" class="nav-link active">
			<i class="nav-icon fas fa-tachometer-alt"></i>
			<p>
				Dashboard
				<i class="right fas fa-angle-left"></i>
			</p>
		</a>
		<ul class="nav nav-treeview">
			<li class="nav-item">
				<a href="index.html" class="nav-link active">
					<i class="far fa-circle nav-icon"></i>
					<p>Dashboard v1</p>
				</a>
			</li>
			<li class="nav-item">
				<a href="index2.html" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Dashboard v2</p>
				</a>
			</li>
			<li class="nav-item">
				<a href="index3.html" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Dashboard v3</p>
				</a>
			</li>
		</ul>
	</li>
	-->
	<li class="nav-item">
		<a href="{{url('admin/')}}" class="nav-link">
			<i class="nav-icon fas fa-th"></i>
			<p>
				Home
				<span class="right badge badge-danger">New</span>
			</p>
		</a>
	</li>
	
	<li class="nav-item has-treeview">
		<a href="#" class="nav-link">
			<i class="nav-icon far fa-user"></i>
			<p>
				Users
				<i class="fas fa-angle-left right"></i>
			</p>
		</a>
		<ul class="nav nav-treeview">
			<li class="nav-item">
				<a href="{{url('admin/manage-users')}}" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Manage Users</p>
				</a>
			</li>
			<li class="nav-item">
				<a href="{{url('admin/add-user')}}" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Add User</p>
				</a>
			</li>
		</ul>
	</li>

	<li class="nav-header">MULTI LEVEL EXAMPLE</li>

	<li class="nav-item has-treeview">
		<a href="#" class="nav-link">
			<i class="nav-icon fas fa-circle"></i>
			<p>
				Level 1
				<i class="right fas fa-angle-left"></i>
			</p>
		</a>
		<ul class="nav nav-treeview">
			<li class="nav-item">
				<a href="#" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Level 2</p>
				</a>
			</li>
			<li class="nav-item has-treeview">
				<a href="#" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>
						Level 2
						<i class="right fas fa-angle-left"></i>
					</p>
				</a>
				<ul class="nav nav-treeview">
					<li class="nav-item">
						<a href="#" class="nav-link">
							<i class="far fa-dot-circle nav-icon"></i>
							<p>Level 3</p>
						</a>
					</li>
					<li class="nav-item">
						<a href="#" class="nav-link">
							<i class="far fa-dot-circle nav-icon"></i>
							<p>Level 3</p>
						</a>
					</li>
					<li class="nav-item">
						<a href="#" class="nav-link">
							<i class="far fa-dot-circle nav-icon"></i>
							<p>Level 3</p>
						</a>
					</li>
				</ul>
			</li>
			<li class="nav-item">
				<a href="#" class="nav-link">
					<i class="far fa-circle nav-icon"></i>
					<p>Level 2</p>
				</a>
			</li>
		</ul>
	</li>
</ul>