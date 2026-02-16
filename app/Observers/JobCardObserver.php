<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\JobCard;
use Illuminate\Support\Arr;

class JobCardObserver
{
    /**
     * Handle the JobCard "created" event.
     */
    public function created(JobCard $jobCard): void
    {
        $this->writeAuditLog($jobCard, 'created', null, $this->sanitizeValues($jobCard->getAttributes()));
    }

    /**
     * Handle the JobCard "updated" event.
     */
    public function updated(JobCard $jobCard): void
    {
        $changes = $jobCard->getChanges();

        // Ignore no-op updates and timestamp-only changes.
        $changes = Arr::except($changes, ['updated_at']);

        if ($changes === []) {
            return;
        }

        $oldValues = Arr::only($jobCard->getOriginal(), array_keys($changes));

        $this->writeAuditLog(
            $jobCard,
            'updated',
            $this->sanitizeValues($oldValues),
            $this->sanitizeValues($changes)
        );
    }

    /**
     * Handle the JobCard "deleted" event.
     */
    public function deleted(JobCard $jobCard): void
    {
        $this->writeAuditLog($jobCard, 'deleted', $this->sanitizeValues($jobCard->getOriginal()), null);
    }

    /**
     * Persist an audit log entry.
     */
    private function writeAuditLog(JobCard $jobCard, string $action, ?array $oldValues, ?array $newValues): void
    {
        $request = request();
        $user = auth()->user();

        AuditLog::create([
            'tenant_id' => $jobCard->tenant_id,
            'user_id' => $user?->id,
            'model_type' => JobCard::class,
            'model_id' => $jobCard->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Remove non-auditable/sensitive keys from payload.
     */
    private function sanitizeValues(array $values): array
    {
        return Arr::except($values, [
            'updated_at',
            'deleted_at',
        ]);
    }
}
