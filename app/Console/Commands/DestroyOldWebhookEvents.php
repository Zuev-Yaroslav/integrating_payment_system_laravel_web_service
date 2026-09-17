<?php

namespace App\Console\Commands;

use App\Models\TransactionWebhookEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('payments:destroy-old-webhook-events')]
#[Description('Удаляет старые вебхуки')]
class DestroyOldWebhookEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {

        TransactionWebhookEvent::query()
            ->where('created_at', '<', now()->subHours(5))
            ->chunkById(100, function ($transactionWebhookEvents) {
                TransactionWebhookEvent::whereIn('id', $transactionWebhookEvents->pluck('id'))->delete();
            });
    }
}
