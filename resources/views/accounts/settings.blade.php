@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-6 col-sm-6 col-xs-6">
							<h2 class="title">Ad Accounts</h2>
						</div>
						<div class="col-md-6 col-sm-6 col-xs-6 text-right greeting-button">
							<a href="{{ route('reports.create') }}" class="btn btn-black">Create Report</a>
							<button data-type="{{ $type }}" class="btn btn-black nr_sync_ad_accounts_button">Sync Ad Accounts
								<span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
							</button>
						</div>
					</div>
					<div class="connect_accounts_modal_section"></div>
					<hr/>
					<div class="synchronized_ad_accounts">{!! $ad_accounts_html !!}</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection