<?php

namespace Laravel\Spark\Interactions\Settings\Profile;

use Illuminate\Support\Facades\Validator;
use Laravel\Spark\Events\Profile\TimezoneUpdated;
use Laravel\Spark\Contracts\Interactions\Settings\Profile\UpdateTimezone as Contract;

class UpdateTimezone implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function validator($user, array $data)
    {
        return Validator::make($data, [
            'timezone' => 'required|max:191',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($user, array $data)
    {
        $user->forceFill([
            'timezone' => $data['timezone'],
        ])->save();

        return $user;
    }
}
