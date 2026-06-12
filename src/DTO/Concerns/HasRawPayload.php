<?php

namespace Backtik\KChat\DTO\Concerns;

trait HasRawPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(private readonly array $payload) {}

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->payload;
    }

    protected function string(string $key): ?string
    {
        $value = $this->payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    protected function int(string $key): ?int
    {
        $value = $this->payload[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    /**
     * @return array<mixed>
     */
    protected function array(string $key): array
    {
        $value = $this->payload[$key] ?? [];

        return is_array($value) ? $value : [];
    }
}
