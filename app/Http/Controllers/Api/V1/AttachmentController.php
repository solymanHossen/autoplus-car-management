<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class AttachmentController extends ApiController
{
    /**
     * List attachments for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $attachments = QueryBuilder::for(Attachment::class)
            ->allowedFilters(['attachable_type', 'attachable_id', 'file_type', 'mime_type', 'uploaded_by'])
            ->allowedSorts(['created_at', 'file_size', 'file_name'])
            ->paginate($perPage)
            ->appends($request->query());

        return $this->paginatedResponse(
            $attachments,
            AttachmentResource::class,
            'Attachments retrieved successfully'
        );
    }

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

        return $this->successResponse(new AttachmentResource($attachment), 'Attachment uploaded successfully', 201);
    }

    /**
     * Show a single attachment.
     */
    public function show(Attachment $attachment): JsonResponse
    {
        return $this->successResponse(
            new AttachmentResource($attachment),
            'Attachment retrieved successfully'
        );
    }

    /**
     * Delete an attachment and its stored file.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        $disk = (string) config('tenant.storage_disk', 'local');

        if (Storage::disk($disk)->exists($attachment->file_path)) {
            Storage::disk($disk)->delete($attachment->file_path);
        }

        $attachment->delete();

        return $this->successResponse(null, 'Attachment deleted successfully');
    }

}
