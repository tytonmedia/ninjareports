@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="panel panel-default panel-accounts">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12 col-sm-12 col-xs-12">
						<h2 class="title">Create Report</h2>
					</div>
				</div>
				<hr/>
				<div class="row">
					<form method="post" class="form form-horizontal" action="{{ route('reports.store') }}">
						{{ csrf_field() }}
						<div class="col-md-6">
							<h4 class="title">Report Settings</h4>
							<hr/>
							<div class="form-group">
								<div class="col-md-2">
									<label class="control-label color-black-bold">Name</label>
								</div>
								<div class="col-md-10">
									<input class="form-control" name="title" value="{{ old('title') }}" />
									<div class="error">
										@if ($errors->has('title')) {{ $errors->first('title') }} @endif
									</div>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-2">
									<label class="control-label color-black-bold">Account</label>
								</div>
								<div class="col-md-10">
									<select class="form-control" name="account">
										<option value="">-- Select Account --</option>
										@foreach($accounts as $account)
										<option value="{{ $account->type }}">{{ $account->title }}</option>
										@endforeach
									</select>
									<div class="error">
										@if ($errors->has('account')) {{ $errors->first('account') }} @endif
									</div>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-2">
									<label class="control-label color-black-bold">Frequency</label>
								</div>
								<div class="col-md-10">
									<select class="form-control" name="frequency">
										<option value="daily">Daily</option>
										<option value="weekly">Weekly</option>
										<option value="monthly">Monthly</option>
										<option value="yearly">Yearly</option>
									</select>
									<div class="error">
										@if ($errors->has('frequency')) {{ $errors->first('frequency') }} @endif
									</div>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-2">
									<label class="control-label color-black-bold"></label>
								</div>
								<div class="col-md-10">
									<input class="btn btn-black" type="submit" name="submit" value="Create Report" />
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<h4 class="title">Email Settings</h4>
							<hr/>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection