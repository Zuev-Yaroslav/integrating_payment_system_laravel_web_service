<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasStateMachine
{
    protected static function bootHasStateMachine()
    {
        static::updating(function (Model $record) {
            if ($record->isDirty('status')) {
                $oldStatus = $record->getOriginal('status');
                $newStatus = $record->status;
                $enumClass = "App\\Enums\\" . ucfirst(class_basename($record)) . "Status";

                if (!$newStatus instanceof $enumClass) {
                    $newStatus = $enumClass::from($newStatus);
                }
                if (!$oldStatus instanceof $enumClass) {
                    $oldStatus = $enumClass::from($oldStatus);
                }

                if (!$oldStatus->canTransitionTo($newStatus)) {
                    $exceptionClass = "App\\Exceptions\\" . ucfirst(class_basename($record)) . "StateException";
                    throw $exceptionClass::invalidTransition($record->id, $oldStatus->value, $newStatus->value);
                }
            }
        });
    }
}
