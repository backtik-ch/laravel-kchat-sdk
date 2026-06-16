# Laravel kChat SDK

Laravel SDK for sending kChat messages with a bot token and reading kChat users.

## Installation

```bash
composer require backtik-ch/laravel-kchat-sdk
```

Publish the config:

```bash
php artisan vendor:publish --tag="laravel-kchat-sdk-config"
```

## Configuration

```env
KCHAT_BASE_URL=https://your-kchat-server.example.com
KCHAT_TOKEN=your-bot-token
KCHAT_BOT_USER_ID=your-bot-user-id
KCHAT_TIMEOUT=10
KCHAT_CACHE_ENABLED=true
KCHAT_CACHE_TTL=3600
```

`KCHAT_TOKEN` is the bot token used as `Authorization: Bearer ...`. `KCHAT_BOT_USER_ID` is required for direct messages because the SDK creates or reuses the private channel between the bot and the target user.

## Messages

Send to a channel by channel id:

```php
use Backtik\KChat\Facades\KChat;

$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Hello')
    ->send();
```

Markdown is just message content:

```php
KChat::messages()
    ->toChannel('channel_id')
    ->message('**Hello**')
    ->send();
```

Send a private message by user id:

```php
KChat::messages()
    ->toUserId('user_id')
    ->message('Hello')
    ->send();
```

Send a private message by username:

```php
KChat::messages()
    ->toUsername('simon')
    ->message('**Hello**')
    ->send();
```

Reply in a thread:

```php
KChat::messages()
    ->toChannel('channel_id')
    ->message('Reply')
    ->inThread('root_post_id')
    ->send();
```

Read, update and delete posts:

```php
$post = KChat::messages()->find('post_id');
$thread = KChat::messages()->thread('post_id');
$updated = KChat::messages()->update('post_id', 'Updated message');
$deleted = KChat::messages()->delete('post_id');
```

Override the bot display name or avatar for one message:

```php
KChat::messages()
    ->toChannel('channel_id')
    ->message('Hello')
    ->asBot(name: 'Deploy Bot', avatarUrl: 'https://example.com/avatar.png')
    ->send();
```

Add color attachments:

```php
KChat::messages()
    ->toChannel('channel_id')
    ->message('Build failed')
    ->attachment(
        color: '#d92d20',
        title: 'CI',
        text: 'Tests failed',
    )
    ->send();
```

Attach a local file:

```php
KChat::messages()
    ->toChannel('channel_id')
    ->message('Report')
    ->attachFile('/tmp/report.pdf')
    ->send();
```

## Users

```php
$user = KChat::users()->find('user_id');
$user = KChat::users()->findByUsername('simon');
$user = KChat::users()->findByEmail('simon@example.com');
$users = KChat::users()->list();
```

## Exceptions

Public methods return typed DTOs or throw an exception. They do not return `false`.

```php
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Exceptions\KChatRequestException;
use Backtik\KChat\Facades\KChat;

try {
    $post = KChat::messages()
        ->toUsername('simon')
        ->message('**Hello**')
        ->send();
} catch (KChatNotFoundException $exception) {
    // user or post not found
} catch (KChatRequestException $exception) {
    // request failed
}
```

Available exceptions:

- `KChatConfigurationException`
- `KChatAuthenticationException`
- `KChatAuthorizationException`
- `KChatNotFoundException`
- `KChatValidationException`
- `KChatRequestException`

Exceptions include the endpoint, HTTP status when available, and non-sensitive context. The SDK never includes the bot token in exception messages.

## DTOs

The SDK returns typed DTOs:

- `KChatPost`
- `KChatUser`
- `KChatFileInfo`
- `KChatChannel`

Each DTO exposes common fields with methods such as `id()`, `message()`, `username()`, and keeps the full payload available through `raw()`.

## Security

Keep the bot token in config or `.env`; do not hard-code it in application code. The bot must have permission to post in target channels, create or access direct message channels, upload files, and optionally use message display overrides if your kChat server restricts that feature.

`attachFile()` accepts local files only. It validates that the path exists and is readable before upload.

## Laravel Boost

This package ships with Laravel Boost resources. If your application uses [Laravel Boost](https://laravel.com/docs/13.x/boost), run the installer to publish the package guidelines and skills automatically:

```bash
php artisan boost:install
```

The published resources include:

- **AI guidelines** with an overview of the SDK, required configuration, core conventions, and usage examples.
- **Skill** `kchat-sdk-development` with detailed patterns for sending messages, managing attachments and threads, handling exceptions, and testing.

## Testing

```bash
composer test
```

## Testing in a Local Laravel App

To test this package inside a Laravel application before publishing a release, use a local Composer path repository.

In the Laravel app `composer.json`, add:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-kchat-sdk",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Adjust `url` to the relative path between the Laravel app and this package.

Then install the package in the Laravel app:

```bash
composer require backtik-ch/laravel-kchat-sdk:@dev
```

Publish the config in the Laravel app:

```bash
php artisan vendor:publish --tag="kchat-sdk-config"
```

Configure the Laravel app `.env` with the kChat server URL, bot token, and bot user id.

You can test quickly with Tinker:

```bash
php artisan tinker
```

```php
use Backtik\KChat\Facades\KChat;

KChat::messages()
    ->toChannel('channel_id')
    ->message('Hello from the local SDK')
    ->send();
```

Or with a temporary route:

```php
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Route;

Route::get('/test-kchat', function () {
    $post = KChat::messages()
        ->toChannel('channel_id')
        ->message('Test from Laravel')
        ->send();

    return $post->raw();
});
```

With `"symlink": true`, changes made in this package are immediately used by the Laravel app. If you add or move classes, run this in the Laravel app:

```bash
composer dump-autoload
```
