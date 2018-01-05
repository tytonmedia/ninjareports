<div class="form-group">
	<div class="col-md-3">
		<label class="control-label color-black-bold">Ad Account</label>
	</div>
	<div class="col-md-9">
		@if($ad_accounts && count($ad_accounts) > 0)
		<select class="form-control" name="account">
			@foreach($ad_accounts as $ad_account)
			<option value="{{ $ad_account->ad_account_id }}">{{ $ad_account->title }} ({{ str_replace('act_', '', $ad_account->ad_account_id) }})</option>
			@endforeach
		</select>
		@else
		<div class="error">
			No ad account found. Please
			<a href="{{ route('accounts.settings.facebook') }}">click here</a> to sync facebook ad accounts.
		</div>
		@endif
	</div>
</div>