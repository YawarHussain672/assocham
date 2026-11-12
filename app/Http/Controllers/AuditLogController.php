<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit Trail — immutable governance ledger showing real business,
 * procurement, travel, finance, and access events without demo role-switch noise.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $module = trim($request->string('module')->toString());
        $action = trim($request->string('action')->toString());

        $modules = $this->baseQuery()
            ->select('module')
            ->distinct()
            ->whereNotNull('module')
            ->orderBy('module')
            ->pluck('module');

        $actions = $this->baseQuery()
            ->select('action')
            ->distinct()
            ->whereNotNull('action')
            ->orderBy('action')
            ->pluck('action');

        $query = $this->baseQuery()->with('user')->latest('created_at')->latest('id');

        if ($module !== '') {
            $query->where('module', $module);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('reference', 'like', $like)
                    ->orWhere('detail', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('module', 'like', $like)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like));
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        return view('pages.audit', [
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'filters' => [
                'q' => $search,
                'module' => $module,
                'action' => $action,
            ],
            'stats' => [
                'total' => $this->baseQuery()->count(),
                'today' => $this->baseQuery()->where('created_at', '>=', now()->startOfDay())->count(),
                'modules' => $modules->count(),
                'officers' => $this->baseQuery()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    /**
     * Exclude dev/demo persona-switch traces and anonymous bulk-seeder model events
     * so only genuine operational and governance activity appears.
     */
    private function baseQuery(): Builder
    {
        return AuditLog::query()
            ->where(function (Builder $q) {
                $q->where('module', '!=', 'System')
                    ->orWhere('action', '!=', 'Switched Role');
            })
            ->where(function (Builder $q) {
                $q->whereNotNull('user_id')
                    ->orWhereNotIn('action', ['created', 'updated']);
            });
    }
}
