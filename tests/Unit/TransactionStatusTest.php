<?php

use App\Enums\TransactionStatus;

it('allows the supported transaction lifecycle transitions', function () {
    expect(TransactionStatus::PENDING->canTransitionTo(TransactionStatus::WAITING_FOR_CAPTURE))->toBeTrue()
        ->and(TransactionStatus::PENDING->canTransitionTo(TransactionStatus::SUCCEEDED))->toBeTrue()
        ->and(TransactionStatus::WAITING_FOR_CAPTURE->canTransitionTo(TransactionStatus::SUCCEEDED))->toBeTrue()
        ->and(TransactionStatus::SUCCEEDED->canTransitionTo(TransactionStatus::REFUNDED))->toBeTrue();
});

it('rejects invalid transaction lifecycle transitions', function (TransactionStatus $from, TransactionStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [TransactionStatus::SUCCEEDED, TransactionStatus::CANCELED],
    [TransactionStatus::CANCELED, TransactionStatus::SUCCEEDED],
    [TransactionStatus::REFUNDED, TransactionStatus::SUCCEEDED],
    [TransactionStatus::WAITING_FOR_CAPTURE, TransactionStatus::REFUNDED],
]);
