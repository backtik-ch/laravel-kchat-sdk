## Laravel kChat SDK

`backtik-ch/laravel-kchat-sdk` is a Laravel SDK for sending messages and reading users from a kChat server using a bot token.

### Installation

```bash
composer require backtik-ch/laravel-kchat-sdk
```

Publish the config:

```bash
php artisan vendor:publish --tag="laravel-kchat-sdk-config"
```

### Required Configuration

Set these values in your application `.env` file:

```env
KCHAT_BASE_URL=https://your-kchat-server.example.com
KCHAT_TOKEN=your-bot-token
KCHAT_BOT_USER_ID=your-bot-user-id
KCHAT_TIMEOUT=10
KCHAT_CACHE_ENABLED=true
KCHAT_CACHE_TTL=3600
```

`KCHAT_TOKEN` is used as `Authorization: Bearer ...`. `KCHAT_BOT_USER_ID` is required for direct messages because the SDK creates or reuses the private channel between the bot and the target user.

### Core Conventions

- Always use the `KChat` facade (`Backtik\KChat\Facades\KChat`) instead of resolving `KChatClient` directly.
- `KChat::messages()` returns a `KChatMessages` fluent builder.
- `KChat::users()` returns a `KChatUsers` helper.
- Public methods return typed DTOs (`KChatPost`, `KChatUser`, `KChatFileInfo`) or throw typed exceptions. They never return `false`.
- Catch `KChatNotFoundException` for missing users or posts, and `KChatRequestException` for generic request failures.

### Sending Messages

Send to a channel:

@verbatim
<code-snippet name="Send to channel" lang="php">
use Backtik\KChat\Facades\KChat;

$post = KChat::messages()
    ->toChannel('channel_id')
    ->message('Hello from Laravel')
    ->send();
</code-snippet>
@endverbatim

Send a direct message by user id or username:

@verbatim
<code-snippet name="Send direct message" lang="php">
KChat::messages()
    ->toUserId('user_id')
    ->message('Hello')
    ->send();

KChat::messages()
    ->toUsername('simon')
    ->message('Hello')
    ->send();
</code-snippet>
@endverbatim

Reply in a thread:

@verbatim
<code-snippet name="Reply in thread" lang="php">
KChat::messages()
    ->toChannel('channel_id')
    ->message('Reply')
    ->inThread('root_post_id')
    ->send();
</code-snippet>
@endverbatim

### Attachments and Files

Add color attachments:

@verbatim
<code-snippet name="Color attachment" lang="php">
KChat::messages()
    ->toChannel('channel_id')
    ->message('Build failed')
    ->attachment(
        color: '#d92d20',
        title: 'CI',
        text: 'Tests failed',
    )
    ->send();
</code-snippet>
@endverbatim

Attach a local file:

@verbatim
<code-snippet name="Attach local file" lang="php">
KChat::messages()
    ->toChannel('channel_id')
    ->message('Report')
    ->attachFile('/tmp/report.pdf')
    ->send();
</code-snippet>
@endverbatim

### Reading and Updating Messages

@verbatim
<code-snippet name="Read update delete posts" lang="php">
$post = KChat::messages()->find('post_id');
$thread = KChat::messages()->thread('post_id');
$updated = KChat::messages()->update('post_id', 'Updated message');
$deleted = KChat::messages()->delete('post_id');
</code-snippet>
@endverbatim

### Users

@verbatim
<code-snippet name="Find users" lang="php">
$user = KChat::users()->find('user_id');
$user = KChat::users()->findByUsername('simon');
$user = KChat::users()->findByEmail('simon@example.com');
$users = KChat::users()->list();
</code-snippet>
@endverbatim

### Security

- Keep the bot token in config or `.env`. Never hard-code it.
- The bot must have permission to post in target channels, create or access direct message channels, upload files, and use message display overrides if your kChat server restricts that feature.
- `attachFile()` accepts local files only. The SDK validates that the path exists and is readable before upload.
- Exceptions include the endpoint, HTTP status when available, and non-sensitive context. The SDK never includes the bot token in exception messages.
