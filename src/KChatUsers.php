<?php

namespace Backtik\KChat;

use Backtik\KChat\DTO\KChatUser;

class KChatUsers
{
    public function __construct(private readonly KChatClient $client) {}

    public function find(string $id): KChatUser
    {
        return new KChatUser($this->client->get("users/{$id}"));
    }

    public function findByUsername(string $username): KChatUser
    {
        return new KChatUser($this->client->get("users/username/{$username}"));
    }

    public function findByEmail(string $email): KChatUser
    {
        return new KChatUser($this->client->get("users/email/{$email}"));
    }

    /**
     * @return array<int, KChatUser>
     */
    public function list(int $page = 0, int $perPage = 60): array
    {
        return array_values(array_map(
            static fn (array $user): KChatUser => new KChatUser($user),
            $this->client->get('users', ['page' => $page, 'per_page' => $perPage])
        ));
    }
}
