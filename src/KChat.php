<?php

namespace Backtik\KChat;

class KChat
{
    public function __construct(private readonly KChatClient $client) {}

    public function messages(): KChatMessages
    {
        return new KChatMessages($this->client, $this->users());
    }

    public function users(): KChatUsers
    {
        return new KChatUsers($this->client);
    }
}
