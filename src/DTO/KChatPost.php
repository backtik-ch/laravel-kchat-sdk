<?php

namespace Backtik\KChat\DTO;

use Backtik\KChat\DTO\Concerns\HasRawPayload;

class KChatPost
{
    use HasRawPayload;

    public function id(): ?string
    {
        return $this->string('id');
    }

    public function channelId(): ?string
    {
        return $this->string('channel_id');
    }

    public function userId(): ?string
    {
        return $this->string('user_id');
    }

    public function message(): ?string
    {
        return $this->string('message');
    }

    public function rootId(): ?string
    {
        return $this->string('root_id');
    }

    /**
     * @return array<int, string>
     */
    public function fileIds(): array
    {
        return array_values(array_filter(
            $this->array('file_ids'),
            static fn (mixed $value): bool => is_string($value)
        ));
    }
}
