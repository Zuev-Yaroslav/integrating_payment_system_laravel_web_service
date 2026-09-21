<?php

use App\Enums\OrderStatus;

it('allows pending orders to complete or fail', function (OrderStatus $status) {
    expect(OrderStatus::PENDING->canTransitionTo($status))->toBeTrue();
})->with([
    OrderStatus::COMPLETED,
    OrderStatus::FAILED,
]);

it('does not allow terminal order statuses to transition', function (OrderStatus $from) {
    expect($from->canTransitionTo(OrderStatus::COMPLETED))->toBeFalse()
        ->and($from->canTransitionTo(OrderStatus::FAILED))->toBeFalse();
})->with([
    OrderStatus::COMPLETED,
    OrderStatus::FAILED,
]);

it('allows failed order to transition to pending', function () {
    expect(OrderStatus::FAILED->canTransitionTo(OrderStatus::PENDING))->toBeTrue();
});
