<?php

namespace Backtik\KChat;

use Backtik\KChat\DTO\KChatChannel;

class KChatChannels
{
    public function __construct(private readonly KChatClient $client) {}

    /**
     * List channels the given user belongs to. Defaults to the user authenticated by the configured token.
     *
     * @return array<int, KChatChannel>
     */
    public function listForUser(string $userId = 'me'): array
    {
        return array_values(array_map(
            static fn (array $channel): KChatChannel => new KChatChannel($channel),
            $this->client->get('users/'.rawurlencode($userId).'/channels')
        ));
    }
}
