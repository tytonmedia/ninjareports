@extends('spark::layouts.app') @section('content') @section('page_styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css"
/> @endsection
<div class="spark-screen container">
	<div class="row">
		<div class="col-md-12">
			@include('common.flash')
			<div class="panel panel-default panel-accounts">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-6 col-sm-6 col-xs-6">
							<h1 class="title">Edit Report</h1>
						</div>
						<div class="col-md-6 col-sm-6 col-xs-6 text-right greeting-button">
							<a href="{{ route('reports.index') }}" class="btn btn-black"><i class="fa fa-angle-left" aria-hidden="true"></i>&nbsp; Reports</a>
						</div>
					</div>
					<hr/>
					<div class="row">
						<form method="post" class="form form-horizontal" action="{{ route('reports.update', $report->id) }}">
							{{ csrf_field() }}
							<div class="col-md-6">
								<h4 class="title">Report Settings</h4>
								<hr/>
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label color-black-bold">Name</label>
									</div>
									<div class="col-md-9">
										<input class="form-control" name="title" value="{{ old('title') ? old('title') : $report->title }}" />
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
											<option value="{{ $account->type }}" {{ $account->id == $report->account_id ? 'selected':'' }}>{{ $account->title }}</option>
											@endforeach
										</select>
										<div class="error">
											@if ($errors->has('account_type')) {{ $errors->first('account_type') }} @endif
										</div>
									</div>
								</div>
								<div class="sub_accounts_html">{!! $ad_accounts_html !!}</div>
								<div class="properties_html">{!! $properties_html !!}</div>
								<div class="views_html">{!! $profiles_html !!}</div>
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
											<option value="daily" {{ $report->frequency == 'daily' ? 'selected':'' }} >Daily</option>
											<option value="weekly" {{ $report->frequency == 'weekly' ? 'selected':'' }} >Weekly</option>
											<option value="monthly" {{ $report->frequency == 'monthly' ? 'selected':'' }} >Monthly</option>
											<option value="yearly" {{ $report->frequency == 'yearly' ? 'selected':'' }} >Yearly</option>
										</select>
										<div class="error">
											@if ($errors->has('frequency')) {{ $errors->first('frequency') }} @endif
										</div>
									</div>
									<div class="ends_at_section">
										@if($report->frequency == 'daily')
											<div class="col-md-1">
												<label class="control-label color-black-bold">at</label>
											</div>
											<div class="col-md-5">
												<input type="text" name="ends_at" value="{{ $report->ends_at }}" readonly class="custom-readonly form-control timepicker" />
											</div>
										@endif
										@if($report->frequency == 'weekly')
											<div class="col-md-1">
												<label class="control-label color-black-bold">on</label>
											</div>
											<div class="col-md-5">
												<select name="ends_at" class="form-control">
													<option value="Mon" {{ $report->ends_at == 'Mon' ? 'selected':'' }} >Mon</option>
													<option value="Tue" {{ $report->ends_at == 'Tue' ? 'selected':'' }} >Tue</option>
													<option value="Wed" {{ $report->ends_at == 'Wed' ? 'selected':'' }} >Wed</option>
													<option value="Thu" {{ $report->ends_at == 'Thu' ? 'selected':'' }} >Thu</option>
													<option value="Fri" {{ $report->ends_at == 'Fri' ? 'selected':'' }} >Fri</option>
													<option value="Sat" {{ $report->ends_at == 'Sat' ? 'selected':'' }} >Sat</option>
													<option value="Sun" {{ $report->ends_at == 'Sun' ? 'selected':'' }} >Sun</option>
												</select>
											</div>
										@endif
										@if($report->frequency == 'monthly')
											<div class="col-md-1">
												<label class="control-label color-black-bold">on</label>
											</div>
											<div class="col-md-5">
												<select name="ends_at" class="form-control">
													@for($i=1;$i<=31;$i++)
														<option value="{{ $i }}" {{ $report->ends_at == $i ? 'selected':'' }}>{{ $i }}</option>
													@endfor
												</select>
											</div>
										@endif
										@if($report->frequency == 'yearly')
										@php($y_date = explode('-', $report->ends_at))
											<div class="col-md-1">
												<label class="control-label color-black-bold">on</label>
											</div>
											<div class="col-md-2">
												<select name="ends_at_day" class="form-control">
													@for($i=1;$i<=31;$i++)
														<option value="{{ $i }}" {{ $y_date[1] == $i ? 'selected':'' }}>{{ $i }}</option>
													@endfor
												</select>
											</div>
											<div class="col-md-3">
												<select name="ends_at_month" class="form-control">
													<option value="1" {{ $y_date[0] == 1 ? 'selected':'' }} >Jan</option>
													<option value="2" {{ $y_date[0] == 2 ? 'selected':'' }} >Feb</option>
													<option value="3" {{ $y_date[0] == 3 ? 'selected':'' }} >Mar</option>
													<option value="4" {{ $y_date[0] == 4 ? 'selected':'' }} >Apr</option>
													<option value="5" {{ $y_date[0] == 5 ? 'selected':'' }} >May</option>
													<option value="6" {{ $y_date[0] == 6 ? 'selected':'' }} >Jun</option>
													<option value="7" {{ $y_date[0] == 7 ? 'selected':'' }} >Jul</option>
													<option value="8" {{ $y_date[0] == 8 ? 'selected':'' }} >Aug</option>
													<option value="9" {{ $y_date[0] == 9 ? 'selected':'' }} >Sep</option>
													<option value="10" {{ $y_date[0] == 10 ? 'selected':'' }} >Oct</option>
													<option value="11" {{ $y_date[0] == 11 ? 'selected':'' }} >Nov</option>
													<option value="12" {{ $y_date[0] == 12 ? 'selected':'' }} >Dec</option>
												</select>
											</div>
										@endif
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
										<textarea class="form-control" name="recipients">{{ old('recipients') ? old('recipients') : $report->recipients }}</textarea>
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
											<a href="#none" class="btn btn-default{{ old('attachment_type') == 'none' ? ' active': ($report->attachment_type == 'none' ? ' active':'') }}"
											 data-toggle="tab">
												<input type="radio" name="attachment_type" value="none" {{ old( 'attachment_type')=='none' ? ' checked': ($report->attachment_type == 'none' ? ' checked': '') }} />None
											</a>
											<a href="#pdf" class="btn btn-default{{ old('attachment_type') == 'pdf' ? ' active':($report->attachment_type == 'pdf' ? ' active': '') }}" data-toggle="tab">
												<input type="radio" name="attachment_type" value="pdf" {{ old( 'attachment_type')=='pdf' ? ' checked': ($report->attachment_type == 'pdf' ? ' checked': '') }} />PDF
											</a>
											<a href="#scv" class="btn btn-default{{ old('attachment_type') == 'csv' ? ' active':($report->attachment_type == 'csv' ? ' active': '') }}" data-toggle="tab">
												<input type="radio" name="attachment_type" value="csv" {{ old( 'attachment_type')=='csv' ? ' checked': ($report->attachment_type == 'csv' ? ' checked': '') }} />CSV
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
										<input class="form-control" name="email_subject" value="{{ old('email_subject') ? old('email_subject') : $report->email_subject }}" />
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
										<input class="btn btn-primary" type="submit" name="submit" value="Update Report" />
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
