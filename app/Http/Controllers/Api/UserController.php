<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\UserService;

class UserController extends Controller
{
    //

    public function __construct(UserService $userService = null) {
        $this->userService = $userService;
    }

    public function getIntegrations()
    {
        $accounts =  $this->userService->getActiveIntegrationsOfUser(auth()->user());

        $formattedAccounts =  array_map(function ($integration) {
                return array_only($integration, ['title', 'type']);
            },$accounts->toArray());

        return response()->json([
            'accounts' => $formattedAccounts
            ]);    
    }
}
