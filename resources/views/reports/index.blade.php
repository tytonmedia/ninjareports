@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@if($paused)
								<div class="alert alert-danger">
									<b>Upgrade to Resume</b><br/>
									You have reached the limit of your plan. <a href="{{ url('settings#/subscription') }}">Upgrade your plan</a> to resume your reports.
								</div>
							@endif
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<div class=" greeting-button">
							<a href="{{ route('reports.create') }}"  onClick="ga('send','event','button', 'click', 'create_report');" class="btn btn-black btn-create-report">Create Report&nbsp;&nbsp;<i class="fa fa-caret-right" aria-hidden="true"></i></a>
						</div>
							<h1 class="title">Reports</h1>
							<p>Create, view and edit your email reports below. Click create report to build an automated email report.</p>
						</div>
						
					</div>
					<hr/> @if($all_reports && count($all_reports) > 0)
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive">
							<table class="reports_table table table-stripped accounts_connect_table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Status</th>
										<th>Property/Ad Account</th>
										<th>Integration</th>
										<th>Frequency</th>
										<th>Recipients</th>
										<th style="min-width: 100px">Last Sent</th>
										<th style="min-width: 150px"></th>
									</tr>
								</thead>
								<tbody>
									@foreach($all_reports as $report)
									@php($emails = $report->recipients ? explode(',', $report->recipients) : [])
									@php($is_paused = $report->is_paused)
									<tr class="{{ $is_paused ? 'paused' : 'running' }}">
										<td><a href="{{ route('reports.edit', $report->id) }}"><strong>{{ $report->title }}</strong></a></td>
										<td>{!! $paused || $is_paused ? '<button class="btn btn-xs btn-danger">Paused</button>':'<button class="btn btn-xs btn-success">Active</button>' !!}</td>
										<td>{{ $report->ad_account->title }}</td>
										<td>{{ $report->account->title }}</td>
										<td>{{ ucfirst($report->frequency) }}</td>
										<td>
											@if(count($emails) > 0)
											@foreach($emails as $email)
											{{ $email }}<br/>
											@endforeach
											@endif
										</td>
										<td>
											@if(strtotime($report->sent_at) < strtotime("-1 hours"))
											<span class="recent">
											@endif
											{{ $report->sent_at ? $report->sent_at->diffForHumans() : '-' }}

											@if(strtotime($report->sent_at) < strtotime("-1 hours"))
											</span>
											@endif
									</td>
										<td>
											<a href="{{ route('reports.edit', $report->id) }}" class="btn btn-xs btn-black margin-top-5">Edit</a>
											<a onClick="return confirm('Are you sure you want to delete this report?')" href="{{ route('reports.delete', $report->id) }}" class="btn btn-xs btn-black margin-top-5">Delete</a>
											<button data-report_id="{{ $report->id }}" class="btn btn-black toggle_report margin-top-5" data-status="{{ $is_paused ? 'yes' : 'no' }}">{{ $is_paused ? 'Start' : 'Pause' }}</button>
										</td>
									</tr>
									@endforeach
									<tr>
										<td colspan="8"><span class="pull-right">{{ $all_reports->links() }}</span></td>
									</tr>
								</tbody>
							</table>
</div>
						</div>
					</div>
					@else
					<div class="text-center empty-table">No reports created yet.</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
