@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@include('common.flash')
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
					<hr/> @if($all_reports && count($all_reports) > 0)
					<div class="row">
						<div class="col-md-12">
							@if($paused)
								<div class="alert alert-danger">
									<b>Upgrade to Resume</b><br/>
									You have reached the limit of your plan. <a href="{{ url('settings#/subscription') }}">Upgrade your plan</a> to resume your reports.
								</div>
							@endif
							<div class="table-responsive">
							<table class="table table-stripped accounts_connect_table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Status</th>
										<th>Property/Ad Account</th>
										<th>Account</th>
										<th>Frequency</th>
										<th>Recipients</th>
										<th style="min-width: 100px">Last Sent</th>
										<th style="min-width: 150px"></th>
									</tr>
								</thead>
								<tbody>
									@foreach($all_reports as $report)
									@php($is_paused = $report->is_paused)
									<tr>
										<td>{{ $report->title }}</td>
										<td>{!! $paused || $is_paused ? '<button class="btn btn-xs btn-danger">Paused</button>':'<button class="btn btn-xs btn-success">Active</button>' !!}</td>
										<td>{{ $report->ad_account->title }}</td>
										<td>{{ $report->account->title }}</td>
										<td>{{ ucfirst($report->frequency) }}</td>
										<td>{{ $report->recipients }}</td>
										<td>{{ $report->sent_at ? $report->sent_at->diffForHumans() : '-' }}</td>
										<td>
											<a href="{{ route('reports.edit', $report->id) }}" class="btn btn-xs btn-black">Edit</a>
											<a onClick="return confirm('Are you sure you want to delete this report?')" href="{{ route('reports.delete', $report->id) }}" class="btn btn-xs btn-black">Delete</a>
											<button data-report_id="{{ $report->id }}" class="btn btn-black toggle_report" data-status="{{ $is_paused ? 'yes' : 'no' }}">{{ $is_paused ? 'Start' : 'Pause' }}</button>
										</td>
									</tr>
									@endforeach
									<tr>
										<td colspan="7"><span class="pull-right">{{ $all_reports->links() }}</span></td>
									</tr>
								</tbody>
							</table>
</div>
						</div>
					</div>
					@else
					<div class="text-center">No reports created yet.</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
