<?php

use App\Services\Security\IpAddressChecker;

it('checks IPv4 addresses in and outside a subnet', function () {
    expect(IpAddressChecker::ipInSubnet('185.71.76.10', '185.71.76.0/27'))->toBeTrue()
        ->and(IpAddressChecker::ipInSubnet('185.71.76.40', '185.71.76.0/27'))->toBeFalse();
});

it('checks IPv6 addresses and rejects malformed or mixed protocol input', function () {
    expect(IpAddressChecker::ipInSubnet('2a02:5180:1::1', '2a02:5180::/32'))->toBeTrue()
        ->and(IpAddressChecker::ipInSubnet('not-an-ip', '2a02:5180::/32'))->toBeFalse()
        ->and(IpAddressChecker::ipInSubnet('127.0.0.1', '::/0'))->toBeFalse();
});
