@extends('spark::layouts.app') @section('content')
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-6 col-sm-6 col-xs-6">
							<h2 class="title">Create Report</h2>
						</div>
						<div class="col-md-6 col-sm-6 col-xs-6 text-right greeting-button">
							<a href="{{ route('reports.index') }}" class="btn btn-black">Reports</a>
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
									<div class="col-md-3">
										<label class="control-label color-black-bold">Name</label>
									</div>
									<div class="col-md-9">
										<input class="form-control" name="title" value="{{ old('title') }}" />
										<div class="error">
											@if ($errors->has('title')) {{ $errors->first('title') }} @endif
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Account Type
											<span class="ad-account-types-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
										</label>
									</div>
									<div class="col-md-9">
										<select class="form-control ad-account-types" name="account_type">
											<option value="">-- Select Account Type --</option>
											@foreach($accounts as $account)
											<option value="{{ $account->type }}">{{ $account->title }}</option>
											@endforeach
										</select>
										<div class="error">
											@if ($errors->has('account_type')) {{ $errors->first('account_type') }} @endif
										</div>
									</div>
								</div>
								<div class="sub_accounts_html"></div>
								<div class="form-group">
									<div class="col-md-3"></div>
									<div class="col-md-9">
										<div class="error">
											@if ($errors->has('account')) {{ $errors->first('account') }} @endif
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Frequency</label>
									</div>
									<div class="col-md-9">
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
							</div>
							<div class="col-md-6">
								<h4 class="title">Email Settings</h4>
								<hr/>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Recipients</label>
									</div>
									<div class="col-md-9">
										<label>
											<i>(Comma seperated emails)</i>
										</label>
										<textarea class="form-control" name="recipients">{{ old('recipients') }}</textarea>
										<div class="error">
											@if ($errors->has('recipients')) {{ $errors->first('recipients') }} @endif
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Attachment</label>
									</div>
									<div class="col-md-9">
										<div id="tab" class="btn-group btn-group-justified" data-toggle="buttons">
											<a href="#none" class="btn btn-default{{ old('attachment_type') == 'none' ? ' active': (!old('attachment_type') ? ' active':'') }}"
											 data-toggle="tab">
												<input type="radio" name="attachment_type" value="none" {{ old( 'attachment_type')=='none' ? 'checked': (!old(
												 'attachment_type') ? 'checked': '') }} />None
											</a>
											<a href="#pdf" class="btn btn-default{{ old('attachment_type') == 'pdf' ? ' active':'' }}" data-toggle="tab">
												<input type="radio" name="attachment_type" value="pdf" {{ old( 'attachment_type')=='pdf' ? 'checked': '' }} />PDF
											</a>
											<a href="#scv" class="btn btn-default{{ old('attachment_type') == 'csv' ? ' active':'' }}" data-toggle="tab">
												<input type="radio" name="attachment_type" value="csv" {{ old( 'attachment_type')=='csv' ? 'checked': '' }} />CSV
											</a>
										</div>
										<div class="error">
											@if ($errors->has('attachment_type')) {{ $errors->first('attachment_type') }} @endif
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Subject</label>
									</div>
									<div class="col-md-9">
										<input class="form-control" name="email_subject" value="{{ old('email_subject') }}" />
										<div class="error">
											@if ($errors->has('email_subject')) {{ $errors->first('email_subject') }} @endif
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold"></label>
									</div>
									<div class="col-md-9">
										<input class="btn btn-black" type="submit" name="submit" value="Create Report" />
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection