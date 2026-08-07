<?php

namespace App\Http\Controllers;

use App\Support\PendingItems;
use Illuminate\View\View;

/**
 * Notifications — last Phase 5 (Polish) page. Replaces the mock
 * initNotificationsPage() (public/js/app.js) with the same real
 * cross-module aggregation the Dashboard's "Items Awaiting My Approval"
 * panel uses — see App\Support\PendingItems. There's no "read/unread"
 * concept modeled anywhere in the schema, so this is deliberately framed
 * as a live action list, not an inbox: an item simply stops appearing
 * once you act on it (approve/reject/resolve), rather than being
 * marked-read while the underlying work is still outstanding. The old
 * mock page's "Mark all as read" button is not reproduced — there's
 * nothing real for it to do.
 */
class NotificationsController extends Controller
{
    public function index(): View
    {
        return view('pages.notifications', [
            'items' => PendingItems::for(request()->user()),
        ]);
    }
}
