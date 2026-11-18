<?php

namespace Backtik\KChat;

use Backtik\KChat\DTO\KChatChannel;

class KChatChannels
{
    private const PUBLIC_CHANNELS_PER_PAGE = 60;

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
     * List public channels in the teams the authenticated user belongs to.
     *
     * @return array<int, KChatChannel>
     */
    public function public(): array
    {
        $channels = [];

        foreach ($this->client->get('users/me/teams') as $team) {
            if (! is_array($team) || ! is_string($team['id'] ?? null) || trim($team['id']) === '') {
                continue;
            }

            foreach ($this->listPublicForTeam($team['id']) as $channel) {
                $id = $channel->id();

                if (! is_string($id) || trim($id) === '') {
                    $channels[] = $channel;

                    continue;
                }

                $channels[$id] = $channel;
            }
        }

        return array_values($channels);
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

    /**
     * @return array<int, KChatChannel>
     */
    private function listPublicForTeam(string $teamId): array
    {
        $channels = [];
        $page = 0;

        do {
            $response = $this->client->get(
                'teams/'.rawurlencode($teamId).'/channels',
                ['page' => $page, 'per_page' => self::PUBLIC_CHANNELS_PER_PAGE]
            );

            foreach ($response as $channel) {
                if (is_array($channel)) {
                    $channels[] = new KChatChannel($channel);
                }
            }

            $hasNextPage = count($response) === self::PUBLIC_CHANNELS_PER_PAGE;
            $page++;
        } while ($hasNextPage);

        return $channels;
    }
}
