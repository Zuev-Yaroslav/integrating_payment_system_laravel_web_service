<?php

use App\Http\Middleware\YookassaIpWhitelist;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('allows requests from a YooKassa trusted subnet', function () {
    $request = Request::create('/payments/callback', 'POST', server: ['REMOTE_ADDR' => '185.71.76.10']);
    $response = (new YookassaIpWhitelist)->handle($request, fn () => response('accepted'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('accepted');
});

it('rejects requests from an untrusted IP', function () {
    $request = Request::create('/payments/callback', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']);
    $response = (new YookassaIpWhitelist)->handle($request, fn () => response('accepted'));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('Unauthorized IP');
});
