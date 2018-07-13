<div class="form-group">
    <div class="col-md-3">
        <label class="control-label color-black-bold">Profiles</label>
    </div>
    <div class="col-md-9">
        @if($profiles && count($profiles) > 0)
            <select class="form-control" name="profile">
                @foreach($profiles as $profile)
                    <option value="{{ $profile->view_id }}" {{ isset($profile_id) && $profile_id == $profile->id ? 'selected':''}}>{{ $profile->name ? $profile->name : $profile->view_id }}</option>
                @endforeach
            </select>
        @else
            <div class="error">
                No profiles found. Please
                <a href="{{ route('accounts.setting', $type) }}">click here</a> to sync {{ $type }} ad accounts.
            </div>
        @endif
    </div>
</div>