<?php

namespace App\Services\Exception;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorReporter
{
    public function report(Throwable $exception, array $context = [])
    {
        try {
            $endpoint = config('monitoring.endpoint');

            if (!$endpoint) {
                Log::warning('Monitoring endpoint not configured');
                return;
            }

            Log::info('ErrorReporter: Reporting error to monitoring...', [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            $payload = [
                'app_name'     => config('app.name'),
                'platform'     => 'laravel',
                'app_version'  => config('app.version', '1.0.0'),
                'environment'  => app()->environment(),
                'severity'     => $this->detectSeverity($exception),
                'message'      => $exception->getMessage(),
                'stack_trace'  => $exception->getTraceAsString(),
                'url'          => request()?->fullUrl(),
                'user_id' => auth()->check() ? (string) auth()->id() : null,
                'device'       => request()?->userAgent(),
            ];

            Http::withHeaders([
                'X-API-KEY' => config('monitoring.api_key'),
            ])
                ->acceptJson()
                ->post(config('monitoring.endpoint'), $payload);

            Log::info('ErrorReporter: Error reported successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to send error report', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    protected function detectSeverity(\Throwable $e): string
    {
        return match (true) {
            $e instanceof \Illuminate\Validation\ValidationException => 'info',
            $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 'info',

            $e instanceof \Illuminate\Auth\AuthenticationException => 'warning',
            $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'warning',
            $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => 'warning',
            $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => 'warning',

            $e instanceof \Error => 'fatal',

            default => 'error',
        };
    }
}
