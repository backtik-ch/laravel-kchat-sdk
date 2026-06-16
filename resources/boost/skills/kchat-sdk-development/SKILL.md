---
name: kchat-sdk-development
description: Build kChat messaging features using the backtik-ch/laravel-kchat-sdk SDK, including messages, users, attachments, threads and error handling.
---

# kChat SDK Development

## When to use this skill

Use this skill whenever you are writing or modifying code that sends messages, reads users, uploads files, or handles kChat API errors through the `backtik-ch/laravel-kchat-sdk` package.

## Required setup

Ensure the package is installed and configured:

```bash
composer require backtik-ch/laravel-kchat-sdk
php artisan vendor:publish --tag="laravel-kchat-sdk-config"
```

Required environment variables:

```env
KCHAT_BASE_URL=https://your-kchat-server.example.com
KCHAT_TOKEN=your-bot-token
KCHAT_BOT_USER_ID=your-bot-user-id
```

## Patterns

### Send a message with error handling

```php
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Exceptions\KChatRequestException;
use Backtik\KChat\Facades\KChat;

try {
    $post = KChat::messages()
        ->toChannel('channel_id')
        ->message('Deployment finished')
        ->send();
} catch (KChatNotFoundException $exception) {
    // channel or user not found
} catch (KChatRequestException $exception) {
    // generic request failure
}
```

### Send a direct message by username

```php
$post = KChat::messages()
    ->toUsername('simon')
    ->message('Hello Simon')
    ->send();
```

The SDK resolves the username to a user id, creates or reuses a direct channel between the bot user and the target user, caches the channel id when caching is enabled, and then sends the message.

### Reply in a thread

```php
$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Thread reply')
    ->inThread('root_post_id')
    ->send();
```

### Add color attachments

```php
$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Build status')
    ->attachment(color: '#d92d20', title: 'CI', text: 'Tests failed')
    ->attachment(color: '#16a34a', title: 'CI', text: 'Deploy succeeded')
    ->send();
```

### Upload a local file

```php
$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Report')
    ->attachFile('/tmp/report.pdf')
    ->send();
```

`attachFile()` accepts a string path or a `SplFileInfo` / `UploadedFile` instance. The file must exist and be readable on the local filesystem.

### Update or delete a post

```php
$updated = KChat::messages()->update('post_id', 'Updated message');
$deleted = KChat::messages()->delete('post_id');
```

### Find users

```php
$user = KChat::users()->find('user_id');
$user = KChat::users()->findByUsername('simon');
$user = KChat::users()->findByEmail('simon@example.com');
$users = KChat::users()->list(page: 0, perPage: 60);
```

### Override bot display for one message

```php
$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Hello')
    ->asBot(name: 'Deploy Bot', avatarUrl: 'https://example.com/avatar.png')
    ->send();
```

Bot display overrides may require server-side configuration or permissions.

## Exception mapping

| Exception                    | Typical cause                                      |
|------------------------------|----------------------------------------------------|
| `KChatConfigurationException`| Missing or invalid base URL, token or bot user id  |
| `KChatAuthenticationException`| 401 response                                      |
| `KChatAuthorizationException`| 403 response                                       |
| `KChatNotFoundException`     | 404 response, missing user, post or channel        |
| `KChatValidationException`   | 422 response                                       |
| `KChatRequestException`      | Any other failed request                           |

Always prefer catching specific exceptions before the generic `KChatRequestException`.

## Testing

Mock the Laravel HTTP client instead of the SDK internals:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/api/v4/users/username/simon' => Http::response([
        'id' => 'user-id',
        'username' => 'simon',
    ]),
    '*/api/v4/channels/direct' => Http::response([
        'id' => 'direct-channel-id',
    ]),
    '*/api/v4/posts' => Http::response([
        'id' => 'post-id',
        'message' => 'Hello',
    ]),
]);
```

Do not mock `KChatClient` directly. The SDK builds on Laravel's HTTP client, so `Http::fake()` covers the whole flow.

## Anti-patterns

- Do not reuse a `KChatMessages` builder instance across multiple sends unless you intentionally want to keep the previous state. The builder is mutable.
- Do not resolve `KChatClient` from the container directly. Use the `KChat` facade or the `KChat` service.
- Do not expose the bot token in logs, exception rendering, or user-facing output.
- Do not pass remote URLs or user-controlled paths to `attachFile()` without validation. The method expects a local, readable file path.
