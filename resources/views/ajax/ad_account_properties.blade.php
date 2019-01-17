<div class="form-group">
    <div class="col-md-3">
        <label class="control-label color-black-bold">Properties
            <span class="analytics-views-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
        </label>
    </div>
    <div class="col-md-9">
        @if($properties && count($properties) > 0)
            <select data-type="{{ $type }}" data-ad_account="{{ $account }}" class="form-control ninja-ad-property" name="sources[{{ $ad_account->account_id }}][property]">
                @foreach($properties as $property)
                    <option value="{{ $property->property }}" {{ isset($property_id) && $property_id == $property->id ? 'selected':'' }}>{{ $property->name ? $property->name : $property->property }}</option>
                @endforeach
            </select>
        @else
            <div class="error">
                No properties found. Please
                <a href="{{ route('accounts.setting', $type) }}">click here</a> to sync {{ $type }} ad accounts.
            </div>
        @endif
    </div>
</div>