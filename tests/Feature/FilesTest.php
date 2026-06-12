<?php

use Backtik\KChat\Exceptions\KChatRequestException;
use Backtik\KChat\Facades\KChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('kchat-sdk.base_url', 'https://kchat.example.com');
    config()->set('kchat-sdk.token', 'secret-token');
    config()->set('kchat-sdk.bot_user_id', 'bot-id');
});

it('uploads files before sending post with file ids', function () {
    $file = tempnam(sys_get_temp_dir(), 'kchat-report-');
    file_put_contents($file, 'report');

    Http::fake([
        'https://kchat.example.com/api/v4/files' => Http::response(['file_infos' => [['id' => 'file-id']]]),
        'https://kchat.example.com/api/v4/posts' => Http::response(['id' => 'post-id', 'file_ids' => ['file-id']]),
    ]);

    $post = KChat::messages()->toChannel('channel-id')->message('Report')->attachFile($file)->send();

    expect($post->fileIds())->toBe(['file-id']);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.example.com/api/v4/files'
        && str_contains($request->body(), 'channel-id'));
    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.example.com/api/v4/posts'
        && $request['file_ids'] === ['file-id']);
});

it('throws when attached file does not exist', function () {
    KChat::messages()->toChannel('channel-id')->message('Report')->attachFile('/tmp/missing-kchat-file.pdf')->send();
})->throws(KChatRequestException::class, 'does not exist');

it('throws when upload response does not contain a file id', function () {
    $file = tempnam(sys_get_temp_dir(), 'kchat-report-');
    file_put_contents($file, 'report');

    Http::fake([
        'https://kchat.example.com/api/v4/files' => Http::response(['file_infos' => []]),
    ]);

    KChat::messages()->toChannel('channel-id')->message('Report')->attachFile($file)->send();
})->throws(KChatRequestException::class, 'file id');
