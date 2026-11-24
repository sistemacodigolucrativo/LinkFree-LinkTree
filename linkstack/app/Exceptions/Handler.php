<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Exception $e) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException || $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                if (is_file(base_path('INSTALLING')) || is_file(base_path('INSTALLERLOCK')) || !\Illuminate\Support\Facades\Route::has('login')) return response('CSRF token mismatch', 419);
                return redirect()->route('login');
            };
        });
    }
}
