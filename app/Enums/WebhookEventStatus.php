<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case RECEIVED = "received";
    case PROCESSING = "processing";
    case PROCESSED = "processed";
    case SKIPPED = "skipped";
}
