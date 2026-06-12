<?php

namespace Backtik\KChat\DTO;

use Backtik\KChat\DTO\Concerns\HasRawPayload;

class KChatFileInfo
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

    public function mimeType(): ?string
    {
        return $this->string('mime_type');
    }

    public function size(): ?int
    {
        return $this->int('size');
    }
}
