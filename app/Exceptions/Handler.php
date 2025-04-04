<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Tymon\JWTAuth\Facades\JWTAuth;;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
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
    }

    public function render($request, Throwable $exception)
    {
        // Si la petición es de tipo JSON (como en APIs), dejamos que Laravel lo maneje
        if ($request->expectsJson()) {
            return parent::render($request, $exception);
        }
    
        // Obtenemos la respuesta por defecto de Laravel
        $response = parent::render($request, $exception);
    
        // Si el error es del tipo 500, mostramos nuestra vista personalizada
        if ($response->getStatusCode() === 500) {
            return response()->view('errors.server', [], 500);
        }
    
        // Para cualquier otro tipo de error, dejamos la respuesta tal como Laravel la genera
        return $response;
    }
    
        
}
