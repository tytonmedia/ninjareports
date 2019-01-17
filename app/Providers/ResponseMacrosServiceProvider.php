<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Response;

class ResponseMacrosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function ($data=null,$message='Successfull') {
            return Response::json([
                    'message' => $message,
                    'errors'  => null,
                    'data' => $data,
                ]);
        });

        Response::macro('error', function ($message = null,$errors = null,$status = 400) {
            return Response::json([
                'message'         => $message?:$status.' error',
                'errors'          => $errors,
                'status_code'     => $status,
            ], $status);
        });

        Response::macro('pagination',function ($paginate,$key=null,$message='Successfull') {
            $paginatedData = $paginate->toArray();
            $responseData = [
                'message' => $message,
                'errors' => null
            ];
            if ($key) {
                $responseData['data'][$key] = array_get($paginatedData,'data');
            } else {
                $responseData['data'] = array_get($paginatedData,'data');
            }
            return array_merge($responseData,array_except($paginatedData, ['data']));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
