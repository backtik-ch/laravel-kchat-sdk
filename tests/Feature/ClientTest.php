<?php

use Backtik\KChat\Exceptions\KChatAuthenticationException;
use Backtik\KChat\Exceptions\KChatAuthorizationException;
use Backtik\KChat\Exceptions\KChatNotFoundException;
use Backtik\KChat\Exceptions\KChatRequestException;
use Backtik\KChat\Exceptions\KChatValidationException;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com/');
    config()->set('kchat-sdk.token', 'secret-token');
    config()->set('kchat-sdk.bot_user_id', 'bot-id');
    config()->set('kchat-sdk.timeout', 7);
});

it('sends bearer token accept json and uses configured base url', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/user-id' => Http::response(['id' => 'user-id']),
    ]);

    $user = KChat::users()->find('user-id');

    expect($user->id())->toBe('user-id');
    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://kchat.example.com/api/v4/users/user-id'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request->hasHeader('Accept', 'application/json');
    });
});

it('maps failed responses to dedicated exceptions without leaking token', function (int $status, string $exceptionClass) {
    Http::fake([
        'https://kchat.example.com/api/v4/users/user-id' => Http::response(['message' => 'API failed'], $status),
    ]);

    try {
        KChat::users()->find('user-id');
    } catch (Throwable $exception) {
        expect($exception)->toBeInstanceOf($exceptionClass)
            ->and($exception->getMessage())->not->toContain('secret-token');

        return;
    }

    $this->fail('Expected exception was not thrown.');
})->with([
    [401, KChatAuthenticationException::class],
    [403, KChatAuthorizationException::class],
    [404, KChatNotFoundException::class],
    [422, KChatValidationException::class],
    [500, KChatRequestException::class],
]);
