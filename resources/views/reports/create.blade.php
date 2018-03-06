@extends('spark::layouts.app') @section('content') @section('page_styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css"
/> @endsection
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@if($paused)
				<div class="alert alert-danger">
					<b>Upgrade to Resume</b><br/>
					You have reached the limit of your plan. <a onClick="ga('send', 'event', 'button', 'click', 'upgrade_alert_create');" href="{{ url('settings#/subscription') }}">Upgrade your plan</a> to resume your reports.
				</div>
			@endif
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<h1 class="title">Create Report</h1>
							<p>Build your automated email reports below. Select your integration, schedule your reports, add your recipients and enjoy automated reporting.</p>
						</div>
					</div>
					<div class="row">
						<form method="post" class="form form-horizontal" action="{{ route('reports.store') }}" onsubmit="ga('send','event','button', 'click', 'start_report');">
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
										<label class="control-label color-black-bold">Data Source
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
								<div class="properties_html"></div>
								<div class="views_html"></div>
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
									<div class="col-md-3">
										<select class="form-control frequency" name="frequency">
											<option value="daily">Daily</option>
											<option value="weekly">Weekly</option>
											<option value="monthly">Monthly</option>
											<option value="yearly">Yearly</option>
										</select>
										<div class="error">
											@if ($errors->has('frequency')) {{ $errors->first('frequency') }} @endif
										</div>
									</div>
									<div class="ends_at_section">
										<div class="col-md-1">
											<label class="control-label color-black-bold">at</label>
										</div>
										<div class="col-md-5">
											<input type="text" name="ends_at" readonly class="custom-readonly form-control timepicker" />
											<div class="help-block"><a style="font-size: 12px" href="{{ url('settings') }}">Set Timezone</a> to send reports at the correct time.</div>
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
										<textarea class="form-control" name="recipients">{{ old('recipients') }}</textarea>
										<label class="help">(Comma seperated emails)</label>
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
											<a href="#scv" class="hidden btn btn-default{{ old('attachment_type') == 'csv' ? ' active':'' }}" data-toggle="tab">
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
										<input class="btn btn-lg btn-primary" type="submit" name="submit" value="Create Report" />
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
@endsection @section('page_scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
@endsection
