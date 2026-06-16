<?php

namespace Backtik\KChat;

use Backtik\KChat\DTO\KChatFileInfo;
use Backtik\KChat\DTO\KChatPost;
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Exceptions\KChatRequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use SplFileInfo;

class KChatMessages
{
    private ?string $channelId = null;

    private ?string $recipientUserId = null;

    private ?string $recipientUsername = null;

    private ?string $message = null;

    private ?string $rootId = null;

    private ?string $botName = null;

    private ?string $botAvatarUrl = null;

    /**
     * @var array<int, array{color?: string, title?: string, text?: string}>
     */
    private array $attachments = [];

    /**
     * @var array<int, string|SplFileInfo>
     */
    private array $files = [];

    public function __construct(
        private readonly KChatClient $client,
        private readonly KChatUsers $users,
    ) {}

    public function toChannel(string $channelId): self
    {
        $this->channelId = $channelId;
        $this->recipientUserId = null;
        $this->recipientUsername = null;

        return $this;
    }

    public function toUserId(string $userId): self
    {
        $this->recipientUserId = $userId;
        $this->recipientUsername = null;
        $this->channelId = null;

        return $this;
    }

    public function toUsername(string $username): self
    {
        $this->recipientUsername = $username;
        $this->recipientUserId = null;
        $this->channelId = null;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function inThread(string $rootPostId): self
    {
        $this->rootId = $rootPostId;

        return $this;
    }

    public function asBot(?string $name = null, ?string $avatarUrl = null): self
    {
        $this->botName = $name;
        $this->botAvatarUrl = $avatarUrl;

        return $this;
    }

    public function attachment(?string $color = null, ?string $title = null, ?string $text = null): self
    {
        $attachment = array_filter([
            'color' => $color,
            'title' => $title,
            'text' => $text,
        ], static fn (?string $value): bool => $value !== null);

        $this->attachments[] = $attachment;

        return $this;
    }

    public function attachFile(string|SplFileInfo $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function send(): KChatPost
    {
        $channelId = $this->resolveChannelId();
        $payload = [
            'channel_id' => $channelId,
            'message' => $this->message ?? '',
        ];

        if ($this->rootId !== null) {
            $payload['root_id'] = $this->rootId;
        }

        if ($this->botName !== null) {
            $payload['props']['override_username'] = $this->botName;
        }

        if ($this->botAvatarUrl !== null) {
            $payload['props']['override_icon_url'] = $this->botAvatarUrl;
        }

        if ($this->attachments !== []) {
            $payload['props']['attachments'] = $this->attachments;
        }

        $fileIds = $this->uploadFiles($channelId);

        if ($fileIds !== []) {
            $payload['file_ids'] = $fileIds;
        }

        try {
            return new KChatPost($this->client->post('posts', $payload));
        } catch (KChatRequestException $exception) {
            if ($this->botName !== null || $this->botAvatarUrl !== null) {
                $class = get_class($exception);

                throw new $class(
                    $exception->getMessage().' Bot display overrides may require server configuration or permissions.',
                    $exception->endpoint,
                    $exception->status,
                    $exception->context,
                    $exception
                );
            }

            throw $exception;
        }
    }

    public function find(string $postId): KChatPost
    {
        return new KChatPost($this->client->get('posts/'.rawurlencode($postId)));
    }

    /**
     * @return array<int, KChatPost>
     */
    public function thread(string $postId): array
    {
        $response = $this->client->get('posts/'.rawurlencode($postId).'/thread');

        if (isset($response['posts']) && is_array($response['posts'])) {
            return array_map(
                static fn (array $post): KChatPost => new KChatPost($post),
                array_values($response['posts'])
            );
        }

        return [];
    }

    public function update(string $postId, string $message): KChatPost
    {
        try {
            return new KChatPost($this->client->put('posts/'.rawurlencode($postId).'/patch', ['message' => $message]));
        } catch (KChatNotFoundException $exception) {
            throw new KChatNotFoundException(
                "KChat post \"{$postId}\" could not be updated. The post may not exist or the bot may not have permission.",
                $exception->endpoint,
                $exception->status,
                $exception->context,
                $exception
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $postId): array
    {
        try {
            return $this->client->delete('posts/'.rawurlencode($postId));
        } catch (KChatNotFoundException $exception) {
            throw new KChatNotFoundException(
                "KChat post \"{$postId}\" could not be deleted. The post may not exist or the bot may not have permission.",
                $exception->endpoint,
                $exception->status,
                $exception->context,
                $exception
            );
        }
    }

    public function fileInfo(string $fileId): KChatFileInfo
    {
        return new KChatFileInfo($this->client->get('files/'.rawurlencode($fileId).'/info'));
    }

    public function downloadFile(string $fileId): string
    {
        return $this->client->download('files/'.rawurlencode($fileId))->body();
    }

    private function resolveChannelId(): string
    {
        if ($this->channelId !== null) {
            return $this->channelId;
        }

        $recipientUserId = $this->recipientUserId;

        if ($this->recipientUsername !== null) {
            try {
                $recipientUserId = $this->users->findByUsername($this->recipientUsername)->id();
            } catch (KChatNotFoundException $exception) {
                throw new KChatNotFoundException(
                    "KChat user \"{$this->recipientUsername}\" was not found.",
                    $exception->endpoint,
                    $exception->status,
                    $exception->context,
                    $exception
                );
            }
        }

        if ($recipientUserId === null || $recipientUserId === '') {
            throw new KChatRequestException('KChat message destination is not configured. Use toChannel(), toUserId(), or toUsername().');
        }

        return $this->directChannelId($recipientUserId);
    }

    private function directChannelId(string $recipientUserId): string
    {
        $botUserId = $this->client->botUserId();
        $cacheKey = "kchat:direct-channel:{$botUserId}:{$recipientUserId}";

        $resolver = function () use ($botUserId, $recipientUserId): string {
            $channel = $this->client->post('channels/direct', [$botUserId, $recipientUserId]);
            $channelId = $channel['id'] ?? null;

            if (! is_string($channelId) || $channelId === '') {
                throw new KChatRequestException('KChat direct channel response did not contain a channel id.', 'channels/direct');
            }

            return $channelId;
        };

        if (! $this->client->cacheEnabled()) {
            return $resolver();
        }

        return Cache::remember($cacheKey, $this->client->cacheTtl(), $resolver);
    }

    /**
     * @return array<int, string>
     */
    private function uploadFiles(string $channelId): array
    {
        $fileIds = [];

        foreach ($this->files as $file) {
            $path = $this->path($file);

            if (! file_exists($path)) {
                throw new KChatRequestException("KChat file \"{$path}\" does not exist.");
            }

            if (! is_readable($path)) {
                throw new KChatRequestException("KChat file \"{$path}\" is not readable.");
            }

            $response = $this->client->attach('files', 'files', (string) file_get_contents($path), basename($path), [
                'channel_id' => $channelId,
            ]);

            $infos = $response['file_infos'] ?? [];
            $id = is_array($infos) ? ($infos[0]['id'] ?? null) : null;

            if (! is_string($id) || $id === '') {
                throw new KChatRequestException('KChat file upload response did not contain a file id.', 'files');
            }

            $fileIds[] = $id;
        }

        return $fileIds;
    }

    private function path(string|SplFileInfo $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getRealPath() ?: $file->getPathname();
        }

        if ($file instanceof SplFileInfo) {
            return $file->getPathname();
        }

        return $file;
    }
}
