<?php

use Backtik\KChat\DTO\KChatPost;
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com');
    config()->set('kchat-sdk.token', 'secret-token');
    config()->set('kchat-sdk.bot_user_id', 'bot-id');
    config()->set('kchat-sdk.cache.enabled', true);
    Cache::flush();
});

it('posts a channel message through posts endpoint', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id', 'message' => '**Hello**']),
    ]);

    $post = KChat::messages()->toChannel('channel-id')->message('**Hello**')->send();

    expect($post)->toBeInstanceOf(KChatPost::class)
        ->and($post->message())->toBe('**Hello**')
        ->and($post->raw())->toHaveKey('id', 'post-id');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://kchat.example.com/api/v4/posts'
        && $request['channel_id'] === 'channel-id'
        && $request['message'] === '**Hello**');
});

it('adds thread bot overrides and attachments to post payload', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id']),
    ]);

    KChat::messages()
        ->toChannel('channel-id')
        ->message('Build failed')
        ->inThread('root-id')
        ->asBot(name: 'Deploy Bot', avatarUrl: 'https://example.com/avatar.png')
        ->attachment(color: '#d92d20', title: 'CI', text: 'Tests failed')
        ->attachment(color: '#f79009', title: 'Lint', text: 'Warnings')
        ->send();

    Http::assertSent(fn ($request): bool => $request['root_id'] === 'root-id'
        && $request['props']['override_username'] === 'Deploy Bot'
        && $request['props']['override_icon_url'] === 'https://example.com/avatar.png'
        && count($request['props']['attachments']) === 2
        && $request['props']['attachments'][0]['color'] === '#d92d20');
});

it('finds threads updates and deletes posts', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/posts/post-id' => Http::response(['id' => 'post-id']),
        'https://kchat.example.com/api/v4/posts/post-id/thread' => Http::response(['posts' => ['post-id' => ['id' => 'post-id']]]),
        'https://kchat.example.com/api/v4/posts/post-id/patch' => Http::response(['id' => 'post-id', 'message' => 'Updated']),
    ]);

    expect(KChat::messages()->find('post-id')->id())->toBe('post-id')
        ->and(KChat::messages()->thread('post-id')[0]->id())->toBe('post-id')
        ->and(KChat::messages()->update('post-id', 'Updated')->message())->toBe('Updated')
        ->and(KChat::messages()->delete('post-id')->id())->toBe('post-id');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), '/posts/post-id/patch')
        && $request['message'] === 'Updated');
    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && str_ends_with($request->url(), '/posts/post-id'));
});

it('sends direct messages by creating a direct channel then posting', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/channels/direct' => Http::response(['id' => 'dm-channel']),
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id']),
    ]);

    KChat::messages()->toUserId('user-id')->message('Hello')->send();
    KChat::messages()->toUserId('user-id')->message('Second')->send();

    Http::assertSentCount(3);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.example.com/api/v4/channels/direct'
        && $request[0] === 'bot-id'
        && $request[1] === 'user-id');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.example.com/api/v4/posts'
        && $request['channel_id'] === 'dm-channel');
});

it('can disable direct channel cache', function () {
    config()->set('kchat-sdk.cache.enabled', false);
    Http::fake([
        'https://kchat.example.com/api/v4/channels/direct' => Http::response(['id' => 'dm-channel']),
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id']),
    ]);

    KChat::messages()->toUserId('user-id')->message('Hello')->send();
    KChat::messages()->toUserId('user-id')->message('Second')->send();

    Http::assertSentCount(4);
});

it('resolves username before sending direct message', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/username/simon' => Http::response(['id' => 'user-id', 'username' => 'simon']),
        'https://kchat.example.com/api/v4/channels/direct' => Http::response(['id' => 'dm-channel']),
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id']),
    ]);

    KChat::messages()->toUsername('simon')->message('Hello')->send();

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/users/username/simon'));
});

it('throws a clear exception when username does not exist', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/username/simon' => Http::response(['message' => 'not found'], 404),
    ]);

    KChat::messages()->toUsername('simon')->message('Hello')->send();
})->throws(KChatNotFoundException::class, 'KChat user "simon" was not found.');
