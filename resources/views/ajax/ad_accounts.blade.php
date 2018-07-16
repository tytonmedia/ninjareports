<div class="form-group">
    <div class="col-md-3">
        <label class="control-label color-black-bold">Account
            <span class="analytics-properties-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
        </label>
    </div>
    <div class="col-md-9">
        @if($ad_accounts && count($ad_accounts) > 0)
            <select data-type="{{ $type }}" class="form-control nr-ad-account" name="account">
                @foreach($ad_accounts as $ad_account)
                    <option value="{{ $ad_account->ad_account_id }}" {{ isset($ad_account_id) && $ad_account_id == $ad_account->id ? 'selected':''}}>{{ $ad_account->title ? $ad_account->title : $ad_account->ad_account_id }}</option>
                @endforeach
            </select>
        @else
            <div class="error">
                No property found. Please
                <a href="{{ route('accounts.setting', $type) }}">click here</a> to sync {{ $type }} ad accounts.
            </div>
        @endif
    </div>
</div>
