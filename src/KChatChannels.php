<?php

namespace Backtik\KChat;

use Backtik\KChat\DTO\KChatChannel;

class KChatChannels
{
    public function __construct(private readonly KChatClient $client) {}

    /**
     * List channels for the user authenticated by the configured token.
     *
     * @return array<int, KChatChannel>
     */
    public function list(): array
    {
        return $this->listForUser('me');
    }

    /**
     * List channels the given user belongs to.
     *
     * @return array<int, KChatChannel>
     */
    public function listForUser(string $userId): array
    {
        return array_values(array_map(
            static fn (array $channel): KChatChannel => new KChatChannel($channel),
            $this->client->get('users/'.rawurlencode($userId).'/channels')
        ));
    }
}
