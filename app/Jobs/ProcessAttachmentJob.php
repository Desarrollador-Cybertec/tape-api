<?php

namespace App\Jobs;

use App\Enums\ProcessingStatusEnum;
use App\Models\Attachment;
use App\Services\AttachmentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessAttachmentJob permanently failed', [
            'attachment_id' => $this->attachment->id,
            'uuid' => $this->attachment->uuid,
            'error' => $e->getMessage(),
        ]);

        $this->attachment->update([
            'processing_status' => ProcessingStatusEnum::FAILED,
            'metadata' => array_merge($this->attachment->metadata ?? [], [
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }
}
