<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Roles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Tables and user-FK columns representing historical operational records.
     * Any user referenced here must be deactivated rather than hard-deleted
     * so cascading deletes and audit gaps never destroy ERP history.
     *
     * @var array<string, array{label: string, columns: list<string>}>
     */
    private const OPERATIONAL_TABLES = [
        'purchase_requisitions' => ['label' => 'Purchase Requisitions', 'columns' => ['created_by']],
        'purchase_requisition_versions' => ['label' => 'PR Revisions', 'columns' => ['user_id']],
        'rfqs' => ['label' => 'RFQs', 'columns' => ['poc_user_id']],
        'cost_comparative_statements' => ['label' => 'CCS Statements', 'columns' => ['approved_by', 'selected_by']],
        'purchase_orders' => ['label' => 'Purchase Orders', 'columns' => ['created_by']],
        'grns' => ['label' => 'GRNs', 'columns' => ['created_by']],
        'vendors' => ['label' => 'Vendor Records', 'columns' => ['submitted_by', 'blacklisted_by']],
        'trfs' => ['label' => 'Travel Requests', 'columns' => ['employee_id']],
        'flight_logs' => ['label' => 'Flight / Booking Logs', 'columns' => ['uploaded_by']],
        'booking_mail_transactions' => ['label' => 'Booking Emails', 'columns' => ['sent_by']],
        'reimbursements' => ['label' => 'Reimbursements', 'columns' => ['employee_id']],
        'vendor_evaluations' => ['label' => 'Vendor Evaluations', 'columns' => ['rated_by']],
        'exceptions' => ['label' => 'Exceptions', 'columns' => ['requested_by', 'decided_by']],
        'grievances' => ['label' => 'Grievances', 'columns' => ['raised_by', 'resolved_by']],
        'grievance_updates' => ['label' => 'Grievance Updates', 'columns' => ['user_id']],
        'approvals' => ['label' => 'Approval Decisions', 'columns' => ['approver_id']],
        'attachments' => ['label' => 'Uploaded Attachments', 'columns' => ['uploaded_by']],
        'annual_budgets' => ['label' => 'Annual Budget Proposals', 'columns' => ['submitted_by']],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'designation',
        'allowed_modules',
        'is_active',
        'deactivated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_modules' => 'array',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->isForceDeleting() && $user->hasOperationalRecords()) {
                throw new \RuntimeException(
                    "Cannot force delete user {$user->name} ({$user->email}) because historical operational records exist. Use soft delete to preserve audit integrity."
                );
            }
        });
    }

    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    /**
     * Returns counts of linked operational records keyed by human-readable label.
     *
     * @return array<string, int>
     */
    public function operationalRecordCounts(): array
    {
        $counts = [];

        foreach (self::OPERATIONAL_TABLES as $table => $meta) {
            $query = DB::table($table)->where(function ($q) use ($meta) {
                foreach ($meta['columns'] as $idx => $col) {
                    if ($idx === 0) {
                        $q->where($col, $this->id);
                    } else {
                        $q->orWhere($col, $this->id);
                    }
                }
            });

            $count = $query->count();
            if ($count > 0) {
                $counts[$meta['label']] = $count;
            }
        }

        return $counts;
    }

    public function hasOperationalRecords(): bool
    {
        return count($this->operationalRecordCounts()) > 0;
    }

    /**
     * Bulk set of user IDs that own or have acted on any operational ERP record.
     * Used on the User Management roster to avoid N+1 queries.
     *
     * @return array<int, true>
     */
    public static function operationalUserIdSet(): array
    {
        $ids = [];

        foreach (self::OPERATIONAL_TABLES as $table => $meta) {
            foreach ($meta['columns'] as $col) {
                $found = DB::table($table)
                    ->whereNotNull($col)
                    ->distinct()
                    ->pluck($col);

                foreach ($found as $id) {
                    $ids[(int) $id] = true;
                }
            }
        }

        return $ids;
    }

    public function roleLabel(): string
    {
        return $this->role ? Roles::label($this->role) : '—';
    }

    public function designationLabel(): string
    {
        return $this->designation ?: $this->roleLabel();
    }

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class, 'created_by');
    }

    public function trfs(): HasMany
    {
        return $this->hasMany(Trf::class, 'employee_id');
    }
}
