<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Log all exceptions for debugging
        \Log::error('Exception caught in handler', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        // Handle CSRF token mismatch (419 error)
        if ($e instanceof TokenMismatchException) {
            // Regenerate the token
            $request->session()->regenerateToken();
            
            // Log the incident for monitoring
            \Log::warning('CSRF Token Mismatch - 419 Error', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]);

            // Redirect back with error message and preserve input (except passwords)
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors([
                    'csrf' => 'Your session has expired due to inactivity. Please try submitting the form again.'
                ]);
        }

        return parent::render($request, $e);
    }
}