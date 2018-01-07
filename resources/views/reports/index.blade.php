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
					<hr/> @if($reports && count($reports) > 0)
					<div class="row">
						<div class="col-md-12">
							<table class="table table-stripped accounts_connect_table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Property/Ad Account</th>
										<th>Account</th>
										<th>Frequency</th>
										<th>Recipients</th>
										<th>Last Sent</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									@foreach($reports as $report)
									<tr>
										<td>{{ $report->title }}</td>
										<td>{{ $report->ad_account->title }}</td>
										<td>{{ $report->account->title }}</td>
										<td>{{ ucfirst($report->frequency) }}</td>
										<td>{{ $report->recipients }}</td>
										<td>{{ $report->created_at->diffForHumans() }}</td>
										<td>
											<a href="{{ route('cron.report', $report->id) }}" class="btn btn-xs btn-black">Edit</a>
										</td>
									</tr>
									@endforeach
									<tr>
										<td colspan="7"><span class="pull-right">{{ $reports->links() }}</span></td>
									</tr>
								</tbody>
							</table>
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