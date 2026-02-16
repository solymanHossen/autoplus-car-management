<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends ApiController
{
    /**
     * Store a secure attachment for a tenant-scoped polymorphic model.
     */
    public function store(StoreAttachmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;

        $attachableMap = StoreAttachmentRequest::attachableMap();
        $attachableClass = $attachableMap[$validated['attachable_type']];

        $file = $request->file('file');
        $disk = (string) config('tenant.storage_disk', 'local');
        $safeExtension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $safeFileName = (string) Str::uuid().($safeExtension ? '.'.$safeExtension : '');

        $directory = "tenants/{$tenantId}/attachments/{$validated['attachable_type']}";
        $path = Storage::disk($disk)->putFileAs($directory, $file, $safeFileName);

        $attachment = Attachment::create([
            'tenant_id' => $tenantId,
            'attachable_type' => $attachableClass,
            'attachable_id' => (int) $validated['attachable_id'],
            'file_name' => basename((string) $file->getClientOriginalName()),
            'file_path' => $path,
            'file_type' => $validated['file_type'] ?? $validated['attachable_type'],
            'file_size' => (int) $file->getSize(),
            'mime_type' => (string) $file->getMimeType(),
            'uploaded_by' => (int) $user->id,
        ]);

        return $this->successResponse([
            'id' => $attachment->id,
            'attachable_type' => $attachment->attachable_type,
            'attachable_id' => $attachment->attachable_id,
            'file_name' => $attachment->file_name,
            'file_path' => $attachment->file_path,
            'file_type' => $attachment->file_type,
            'file_size' => $attachment->file_size,
            'mime_type' => $attachment->mime_type,
            'uploaded_by' => $attachment->uploaded_by,
            'created_at' => $attachment->created_at?->toIso8601String(),
        ], 'Attachment uploaded successfully', 201);
    }
}
