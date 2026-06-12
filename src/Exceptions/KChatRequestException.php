<?php

namespace Backtik\KChat\Exceptions;

use RuntimeException;
use Throwable;

class KChatRequestException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly ?string $endpoint = null,
        public readonly ?int $status = null,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
