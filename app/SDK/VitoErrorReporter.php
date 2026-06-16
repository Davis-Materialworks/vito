<?php

namespace App\SDK;

use Illuminate\Support\Facades\Http;

class VitoErrorReporter
{
    private string $endpoint;

    private string $token;

    private ?string $environment;

    private ?string $release;

    private array $context;

    public function __construct(
        string $endpoint,
        string $token,
        ?string $environment = null,
        ?string $release = null,
    ) {
        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->environment = $environment ?? app()->environment();
        $this->release = $release;
        $this->context = [];
    }

    public static function bootFromConfig(): self
    {
        return new self(
            config('vito-error-reporter.endpoint'),
            config('vito-error-reporter.token'),
            config('vito-error-reporter.environment'),
            config('vito-error-reporter.release'),
        );
    }

    public function withContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function report(\Throwable $exception, ?string $url = null, ?string $method = null): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(5)
                ->post($this->endpoint, [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'stack_trace' => $exception->getTraceAsString(),
                    'environment' => $this->environment,
                    'release' => $this->release,
                    'url' => $url ?? request()->fullUrl(),
                    'request_method' => $method ?? request()->method(),
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'context' => array_merge($this->context, [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ]),
                    'occurred_at' => now()->toIso8601String(),
                ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function capture(\Throwable $e): bool
    {
        return app(self::class)->report($e);
    }

    public static function registerGlobalHandler(): void
    {
        app()->singleton(self::class, fn () => self::bootFromConfig());

        app()->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->reportable(function (\Throwable $e) {
                self::capture($e);
            });
    }
}
