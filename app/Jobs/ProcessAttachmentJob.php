<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Services\AttachmentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Attachment $attachment,
    ) {}

    public function handle(AttachmentProcessingService $service): void
    {
        $service->process($this->attachment);
    }
}
