<?php

namespace Backtik\KChat\DTO;

use Backtik\KChat\DTO\Concerns\HasRawPayload;

class KChatChannel
{
    use HasRawPayload;

    public function id(): ?string
    {
        return $this->string('id');
    }

    public function name(): ?string
    {
        return $this->string('name');
    }

    public function type(): ?string
    {
        return $this->string('type');
    }
}
