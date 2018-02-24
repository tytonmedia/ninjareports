@if($ad_accounts && count($ad_accounts) > 0)
<div class="row">
	<div class="col-md-12">
		<table class="table table-stripped accounts_connect_table">
			<thead>
				<tr>
					<th>Property</th>
					<th>Name</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($ad_accounts as $ad_account)
				<tr>
					<td>
						<span class="color-black-bold">{{ str_replace('act_', '', $ad_account->ad_account_id) }}</span>
					</td>
					<td>{{ $ad_account->title }}</td>
					<td>
						<a class="btn btn-xs btn-danger">Delete</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@else
<div class="text-center">No ad account synchronized. Please click
	<code>Sync Ad Accounts</code> button to sync {{ $type }} accounts.</div>
@endif
