<?php

namespace App\Http\Controllers;

use App\Models\DeadLetterDelivery;
use App\Jobs\ProcessOutboundWebhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dead-Letter Queue admin interface. Supports listing failed deliveries,
 * one-click manual replay, and bulk replay — all through Inertia.
 */
class DlqController extends Controller
{
    public function index(): Response
    {
        $deliveries = DeadLetterDelivery::with(['tenant', 'webhookEvent'])
            ->orderByDesc('failed_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('DlqInspector', [
            'deliveries' => $deliveries,
        ]);
    }

    public function replay(DeadLetterDelivery $delivery): RedirectResponse
    {
        if ($delivery->replayed_at) {
            return back()->with('error', 'Delivery already replayed.');
        }

        $event = $delivery->webhookEvent;
        if (! $event) {
            return back()->with('error', 'Source event missing; cannot replay.');
        }

        // Re-enqueue the outbound dispatch job (fresh attempt budget).
        ProcessOutboundWebhook::dispatch($event)
            ->onQueue('webhook-relay');

        $delivery->markReplayed();

        return back()->with('success', "DLQ delivery #{$delivery->id} re-queued for replay.");
    }

    public function bulkReplay(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:dead_letter_deliveries,id',
        ]);

        $count = 0;
        foreach ($request->input('ids', []) as $id) {
            $delivery = DeadLetterDelivery::find($id);
            if (! $delivery || $delivery->replayed_at) {
                continue;
            }

            $event = $delivery->webhookEvent;
            if (! $event) {
                continue;
            }

            ProcessOutboundWebhook::dispatch($event)
                ->onQueue('webhook-relay');

            $delivery->markReplayed();
            $count++;
        }

        return back()->with('success', "Replayed {$count} DLQ delivery(ies).");
    }
}