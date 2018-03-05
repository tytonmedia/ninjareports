@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@if($paused)
				<div class="alert alert-danger">
					<b>Upgrade to Resume</b><br/>
					You have reached the limit of your plan. <a onClick="ga('send', 'event', 'button', 'click', 'upgrade_alert_integrations');" href="{{ url('settings#/subscription') }}">Upgrade your plan</a> to resume your reports.
				</div>
			@endif
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
								<div class="greeting-button">
							<button class="btn upgrade-btn nr_connect_accounts_button" onClick="ga('send', 'event', 'button', 'click', 'add_integration_dash');"><i class="fa fa-plus-circle" aria-hidden="true"></i> Add Integration
								<span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
							</button>
						</div>
							<h1 class="title">Integrations</h1>
							<p class="">This is where you can integrate your favorite online applications with Ninja Reports. Click Add Integration to allow Ninja Reports to access your account data.</p>
						</div>
					
					</div>
					<div class="connect_accounts_modal_section"></div>
					<hr/> @if($accounts && count($accounts) > 0)
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive">
							<table class="table table-stripped accounts_connect_table">
								<thead>
									<tr>
										<th></th>
										<th>Integration</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									@foreach($accounts as $account)
									@php($img  = $account->type == 'adwords' ? 'adword.png': $account->type.'.png')
									<tr>
										<td>
											<img src="{{ asset('img/'.$img) }}" />
										</td>
										<td>
											<span class="color-black-bold">{{ $account->title }}</span>
											<br/>{{ $account->email }}</td>
										<td>
											{!! $account->status ? '
											<button class="btn btn-xs btn-default">Connected</button>':'
											<a class="btn btn-xs btn-black" href="'.route('connect.'.$account->type).'">Connect</a>' !!}
										</td>
										<td>
											<a class="btn btn-xs btn-primary" href="{{ route('accounts.setting', $account->type) }}">Settings</a>
											<a href="{{ route('account.delete', $account->id) }}" onclick="return confirm('Are you sure?')" class="btn btn-xs btn-danger">Delete</a>
										</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						</div>
					</div>
					@else
					<div class="text-center empty-table">No Integrations.</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
