<?php

namespace Backtik\KChat;

use Backtik\KChat\Exceptions\KChatAuthenticationException;
use Backtik\KChat\Exceptions\KChatAuthorizationException;
use Backtik\KChat\Exceptions\KChatConfigurationException;
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Exceptions\KChatRequestException;
use Backtik\KChat\Exceptions\KChatValidationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class KChatClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->decode($endpoint, $this->request()->get($this->url($endpoint), $query));
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload = []): array
    {
        return $this->decode($endpoint, $this->request()->post($this->url($endpoint), $payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $payload = []): array
    {
        return $this->decode($endpoint, $this->request()->put($this->url($endpoint), $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $endpoint): array
    {
        return $this->decode($endpoint, $this->request()->delete($this->url($endpoint)));
    }

    /**
     * @param  resource|string  $contents
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    public function attach(string $endpoint, string $name, mixed $contents, ?string $filename, array $data = []): array
    {
        return $this->decode(
            $endpoint,
            $this->request()->attach($name, $contents, $filename)->post($this->url($endpoint), $data)
        );
    }

    public function download(string $endpoint): Response
    {
        $response = $this->request()->get($this->url($endpoint));
        $this->throwIfFailed($endpoint, $response);

        return $response;
    }

    public function botUserId(): string
    {
        $botUserId = $this->config['bot_user_id'] ?? null;

        if (! is_string($botUserId) || trim($botUserId) === '') {
            throw new KChatConfigurationException('KChat bot user id is not configured. Set KCHAT_BOT_USER_ID in your .env file.');
        }

        return $botUserId;
    }

    public function cacheEnabled(): bool
    {
        return filter_var($this->config['cache']['enabled'] ?? true, FILTER_VALIDATE_BOOL);
    }

    public function cacheTtl(): int
    {
        return (int) ($this->config['cache']['ttl'] ?? 3600);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->token())
            ->timeout((int) ($this->config['timeout'] ?? 10));
    }

    private function token(): string
    {
        $token = $this->config['token'] ?? null;

        if (! is_string($token) || trim($token) === '') {
            throw new KChatConfigurationException('KChat token is not configured. Set KCHAT_TOKEN in your .env file.');
        }

        return $token;
    }

    private function url(string $endpoint): string
    {
        $baseUrl = $this->config['base_url'] ?? null;

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new KChatConfigurationException('KChat base URL is not configured. Set KCHAT_BASE_URL in your .env file.');
        }

        return rtrim($baseUrl, '/').'/api/v4/'.ltrim($endpoint, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $endpoint, Response $response): array
    {
        $this->throwIfFailed($endpoint, $response);

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function throwIfFailed(string $endpoint, Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $message = $this->responseMessage($response) ?? "KChat request to {$endpoint} failed with status {$status}.";
        $context = ['endpoint' => $endpoint];

        throw match ($status) {
            401 => new KChatAuthenticationException($message, $endpoint, $status, $context),
            403 => new KChatAuthorizationException($message, $endpoint, $status, $context),
            404 => new KChatNotFoundException($message, $endpoint, $status, $context),
            422 => new KChatValidationException($message, $endpoint, $status, $context),
            default => new KChatRequestException($message, $endpoint, $status, $context),
        };
    }

    private function responseMessage(Response $response): ?string
    {
        $json = $response->json();

        if (is_array($json)) {
            foreach (['message', 'error', 'detail'] as $key) {
                if (isset($json[$key]) && is_string($json[$key]) && $json[$key] !== '') {
                    return $json[$key];
                }
            }
        }

        return null;
    }
}
