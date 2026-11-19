<?php

use Backtik\KChat\DTO\KChatChannel;
use Backtik\KChat\Exceptions\KChatAuthorizationException;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com');
    config()->set('kchat-sdk.token', 'secret-token');
});

it('lists channels for the authenticated user', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/me/channels' => Http::response([
            [
                'id' => 'channel-id',
                'name' => 'alerts',
                'display_name' => 'Alerts',
                'type' => 'O',
                'team_id' => 'team-id',
            ],
            [
                'id' => 'private-channel-id',
                'name' => 'operations',
                'display_name' => 'Operations',
                'type' => 'P',
                'team_id' => 'team-id',
            ],
        ]),
    ]);

    $channels = KChat::channels()->list();

    expect($channels)->toHaveCount(2)
        ->and($channels[0])->toBeInstanceOf(KChatChannel::class)
        ->and($channels[0]->id())->toBe('channel-id')
        ->and($channels[0]->name())->toBe('alerts')
        ->and($channels[0]->displayName())->toBe('Alerts')
        ->and($channels[0]->type())->toBe('O')
        ->and($channels[0]->teamId())->toBe('team-id')
        ->and($channels[0]->raw()['display_name'])->toBe('Alerts');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://kchat.example.com/api/v4/users/me/channels'
        && $request->hasHeader('Authorization', 'Bearer secret-token'));
});

it('can list channels for a specific user', function () {
    Http::fake([
        'https://kchat.example.com/api/v4/users/user-id/channels' => Http::response([]),
    ]);

    expect(KChat::channels()->listForUser('user-id'))->toBe([]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.example.com/api/v4/users/user-id/channels');
});

it('paginates public channels with the configured token', function () {
    Http::fakeSequence('https://kchat.example.com/api/v4/teams/team-id/channels*')
        ->push(array_fill(0, 100, ['id' => 'first', 'type' => 'O']))
        ->push([['id' => 'last', 'type' => 'O']]);

    $channels = KChat::channels()->listPublicForTeam('team-id');

    expect($channels)->toHaveCount(101)->and($channels[100]->id())->toBe('last');
    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer secret-token')
        && $request['page'] === 1 && $request['per_page'] === 100);
});

it('returns an empty public channel list', function () {
    Http::fake(['*' => Http::response([])]);

    expect(KChat::channels()->listPublicForTeam('team-id'))->toBe([]);
});

it('reports denied access to public channels', function () {
    Http::fake(['*' => Http::response(['message' => 'Forbidden'], 403)]);

    KChat::channels()->listPublicForTeam('team-id');
})->throws(KChatAuthorizationException::class);
