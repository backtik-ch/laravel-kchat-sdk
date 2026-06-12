<?php

namespace Backtik\KChat\DTO;

use Backtik\KChat\DTO\Concerns\HasRawPayload;

class KChatUser
{
    use HasRawPayload;

    public function id(): ?string
    {
        return $this->string('id');
    }

    public function username(): ?string
    {
        return $this->string('username');
    }

    public function email(): ?string
    {
        return $this->string('email');
    }

    public function firstName(): ?string
    {
        return $this->string('first_name');
    }

    public function lastName(): ?string
    {
        return $this->string('last_name');
    }
}
