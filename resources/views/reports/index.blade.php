@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		@if($active_accounts > 0)
		<div class="panel panel-default panel-accounts">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6 col-sm-6 col-xs-6">
						<h2 class="title">Reports</h2>
					</div>
					<div class="col-md-6 col-sm-6 col-xs-6 text-right greeting-button">
						<a href="{{ route('reports.create') }}" class="btn btn-black btn-create-report">Create Report</a>
					</div>
				</div>
				<hr/>
			</div>
		</div>
		@else
		<div class="panel panel-default panel-grey panel-connect-accounts">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-2 text-center">
						<img class="social-icon" src="/img/social-icon.png" alt="">
					</div>
					<div class="col-md-10">
						<h4 class="title">Connect your Accounts</h4>
						<p>
							Lorem ipsum dolor sit amet, consectetur adipisicing elit. Delectus, ducimus illo iste deserunt, eaque sunt officiis incidunt
							possimus, voluptas itaque eligendi impedit quisquam omnis asperiores nobis dolorem! Nisi, eos, ipsum. Lorem ipsum
							dolor sit amet, consectetur adipisicing elit. Modi nisi voluptas delectus eos libero corporis suscipit porro minima,
							aperiam.
						</p>
						<button class="btn btn-black nr_connect_accounts_button">Connect Accounts
							<span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
						</button>
						<div class="connect_accounts_modal_section"></div>
					</div>
				</div>
			</div>
		</div>
		@endif
	</div>
</div>
@endsection