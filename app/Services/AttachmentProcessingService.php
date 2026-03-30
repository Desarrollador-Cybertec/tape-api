<?php

namespace App\Services;

use App\Enums\ProcessingStatusEnum;
use App\Models\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class AttachmentProcessingService
{
    private const MAX_IMAGE_WIDTH = 2048;
    private const IMAGE_QUALITY = 80;

    public function process(Attachment $attachment): void
    {
        $attachment->update(['processing_status' => ProcessingStatusEnum::PROCESSING]);

        try {
            $localDisk = Storage::disk('local');
            $tmpPath = $attachment->storage_path;

            if (!$localDisk->exists($tmpPath)) {
                throw new \RuntimeException("Temporary file not found: {$tmpPath}");
            }

            $fileContents = $localDisk->get($tmpPath);
            $finalExtension = $attachment->extension;
            $mimeType = $attachment->mime_type;

            // Process images
            if ($attachment->isImage() && $this->isProcessableImage($attachment->mime_type)) {
                [$fileContents, $finalExtension, $mimeType] = $this->processImage($fileContents);
            }

            // Build final storage path
            $storedName = "{$attachment->uuid}.{$finalExtension}";
            $storagePath = $this->buildStoragePath($attachment, $storedName);

            // Upload to Supabase S3
            Storage::disk('supabase')->put($storagePath, $fileContents, [
                'ContentType' => $mimeType,
            ]);

            // Calculate checksum
            $checksum = hash('sha256', $fileContents);

            // Update attachment record
            $attachment->update([
                'disk' => 'supabase',
                'bucket' => config('filesystems.disks.supabase.bucket'),
                'storage_path' => $storagePath,
                'stored_name' => $storedName,
                'mime_type' => $mimeType,
                'extension' => $finalExtension,
                'size_processed' => strlen($fileContents),
                'checksum' => $checksum,
                'processing_status' => ProcessingStatusEnum::READY,
                'processed_at' => now(),
                'uploaded_at' => now(),
            ]);

            // Delete temporary file
            $localDisk->delete($tmpPath);
        } catch (\Throwable $e) {
            Log::error('Attachment processing failed', [
                'attachment_id' => $attachment->id,
                'uuid' => $attachment->uuid,
                'error' => $e->getMessage(),
            ]);

            $attachment->update([
                'processing_status' => ProcessingStatusEnum::FAILED,
                'metadata' => array_merge($attachment->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString(),
                ]),
            ]);

            throw $e;
        }
    }

    private function isProcessableImage(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ]);
    }

    private function processImage(string $fileContents): array
    {
        $manager = new ImageManager(new GdDriver());
        $image = $manager->read($fileContents);

        // Fix EXIF orientation is handled automatically by Intervention v3

        // Resize if exceeds max width, maintaining aspect ratio
        if ($image->width() > self::MAX_IMAGE_WIDTH) {
            $image->scaleDown(width: self::MAX_IMAGE_WIDTH);
        }

        // Encode as WebP
        $encoded = $image->toWebp(quality: self::IMAGE_QUALITY);

        return [(string) $encoded, 'webp', 'image/webp'];
    }

    private function buildStoragePath(Attachment $attachment, string $storedName): string
    {
        if ($attachment->task_id && $attachment->area_id) {
            return "areas/{$attachment->area_id}/tasks/{$attachment->task_id}/{$storedName}";
        }

        if ($attachment->task_id) {
            $areaId = $attachment->task?->area_id;
            if ($areaId) {
                return "areas/{$areaId}/tasks/{$attachment->task_id}/{$storedName}";
            }
            return "tasks/{$attachment->task_id}/{$storedName}";
        }

        if ($attachment->area_id) {
            return "areas/{$attachment->area_id}/documents/{$storedName}";
        }

        return "users/{$attachment->owner_user_id}/private/{$storedName}";
    }
}
