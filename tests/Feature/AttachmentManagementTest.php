<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists, shows, and deletes attachments within tenant scope', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $jobCard = JobCard::factory()->create(['tenant_id' => $tenant->id]);

    $attachment = Attachment::create([
        'tenant_id' => $tenant->id,
        'attachable_type' => JobCard::class,
        'attachable_id' => $jobCard->id,
        'file_name' => 'test.pdf',
        'file_path' => 'tenants/'.$tenant->id.'/attachments/job_card/test.pdf',
        'file_type' => 'job_card',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
        'uploaded_by' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson('/api/v1/attachments')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1);

    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->getJson('/api/v1/attachments/'.$attachment->id)
        ->assertOk()
        ->assertJsonPath('data.id', $attachment->id);

    $this->withHeader('X-Tenant-ID', $tenant->id)
        ->deleteJson('/api/v1/attachments/'.$attachment->id)
        ->assertOk();

    $this->assertDatabaseMissing('attachments', [
        'id' => $attachment->id,
    ]);
});
