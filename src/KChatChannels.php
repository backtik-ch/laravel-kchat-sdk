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
     * List all public channels in a team using the configured token.
     *
     * @return array<int, KChatChannel>
     */
    public function listPublicForTeam(string $teamId): array
    {
        $channels = [];
        $page = 0;
        $perPage = 100;

        do {
            $response = $this->client->get('teams/'.rawurlencode($teamId).'/channels', [
                'page' => $page++,
                'per_page' => $perPage,
            ]);

            foreach ($response as $channel) {
                $channels[] = new KChatChannel($channel);
            }
        } while (count($response) === $perPage);

        return $channels;
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
