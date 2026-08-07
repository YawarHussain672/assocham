<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Writes to the single consolidated audit_logs table on create/update/delete,
 * so every module feeds the Audit Trail (deck page 26) for free instead of
 * each controller having to remember to log manually.
 *
 * Models using this trait may optionally define auditReference(): string to
 * control the human-readable reference shown in the trail (e.g. "PR-0211");
 * falls back to a `*_number`/`reference` attribute, then the primary key.
 */
trait LogsAuditTrail
{
    public static function bootLogsAuditTrail(): void
    {
        static::created(fn ($model) => $model->writeAuditEntry('created'));
        static::updated(fn ($model) => $model->writeAuditEntry('updated'));
        static::deleted(fn ($model) => $model->writeAuditEntry('deleted'));
    }

    public function writeAuditEntry(string $action, ?string $detail = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'module' => class_basename($this),
            'action' => $action,
            'reference' => $this->auditReference(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'detail' => $detail,
        ]);
    }

    public function auditReference(): ?string
    {
        foreach (['pr_number', 'po_number', 'rfq_number', 'ccs_number', 'grn_number', 'trf_number', 'claim_number', 'ticket_number', 'vendor_code', 'ref', 'reference'] as $column) {
            if (array_key_exists($column, $this->attributes)) {
                return $this->attributes[$column];
            }
        }

        return (string) $this->getKey();
    }
}
