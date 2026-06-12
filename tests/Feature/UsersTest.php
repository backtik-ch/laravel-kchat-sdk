<?php

use Backtik\KChat\DTO\KChatUser;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com');
    config()->set('kchat-sdk.token', 'secret-token');
    config()->set('kchat-sdk.bot_user_id', 'bot-id');
});

it('finds users by id username email and list', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/user-id' => Http::response(['id' => 'user-id']),
        'https://kchat.example.com/api/v4/users/username/simon' => Http::response(['id' => 'user-id', 'username' => 'simon']),
        'https://kchat.example.com/api/v4/users/email/simon@example.com' => Http::response(['id' => 'user-id', 'email' => 'simon@example.com']),
        'https://kchat.example.com/api/v4/users*' => Http::response([['id' => 'user-id']]),
    ]);

    expect(KChat::users()->find('user-id'))->toBeInstanceOf(KChatUser::class)
        ->and(KChat::users()->findByUsername('simon')->username())->toBe('simon')
        ->and(KChat::users()->findByEmail('simon@example.com')->email())->toBe('simon@example.com')
        ->and(KChat::users()->list()[0]->id())->toBe('user-id');
});
