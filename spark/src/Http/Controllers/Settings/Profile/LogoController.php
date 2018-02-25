<?php

namespace Laravel\Spark\Http\Controllers\Settings\Profile;

use Illuminate\Http\Request;
use Laravel\Spark\Contracts\Interactions\Settings\Profile\UpdateLogo;
use Laravel\Spark\Http\Controllers\Controller;

class LogoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store the user's profile photo.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->interaction(
            $request, UpdateLogo::class,
            [$request->user(), $request->all()]
        );
    }
}
