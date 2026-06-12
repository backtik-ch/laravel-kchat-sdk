<?php

use Backtik\KChat\Exceptions\KChatConfigurationException;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com');
    config()->set('kchat-sdk.token', 'secret-token');
    config()->set('kchat-sdk.bot_user_id', 'bot-id');
});

it('throws when base url is missing', function () {
    config()->set('kchat-sdk.base_url', null);

    KChat::users()->find('user-id');
})->throws(KChatConfigurationException::class, 'KChat base URL is not configured.');

it('throws when token is missing', function () {
    config()->set('kchat-sdk.token', null);

    KChat::users()->find('user-id');
})->throws(KChatConfigurationException::class, 'KChat token is not configured.');

it('throws when bot user id is missing for direct messages', function () {
    config()->set('kchat-sdk.bot_user_id', null);

    KChat::messages()->toUserId('user-id')->message('Hello')->send();
})->throws(KChatConfigurationException::class, 'KChat bot user id is not configured.');

it('does not call users me while sending direct messages', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/channels/direct' => Http::response(['id' => 'dm-channel']),
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id']),
    ]);

    KChat::messages()->toUserId('user-id')->message('Hello')->send();

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/users/me'));
});
