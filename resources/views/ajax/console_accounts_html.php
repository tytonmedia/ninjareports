@if($console_accounts && count($console_accounts) > 0)
<div class="row">
	<div class="col-md-12">
		<div class="table-responsive">
		<table class="table table-stripped accounts_connect_table">
			<thead>
				<tr>
					<th>Account</th>
					<th>Name</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($console_accounts as $console_account)
				<tr>
					<td>
						<span class="color-black-bold">{{ str_replace('act_', '', $ad_account->ad_account_id) }}</span>
					</td>
					<td>{{ $console_account->siteUrl }}</td>
					<td>
						
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	</div>
</div>
@else
<div class="text-center">No {{ $type }} account synchronized. Please click the
	<code>Sync Account</code> button to sync {{ $type }} accounts.</div>
@endif
