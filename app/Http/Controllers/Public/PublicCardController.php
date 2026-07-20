<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\PublicCardResource;
use App\Models\BusinessCard;
use App\Models\CardContactShare;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public, anonymous endpoints used by the landing page to render a single
 * business card by its public URL slug and to track interactions (QR scans,
 * NFC taps, link clicks).
 *
 * No authentication is required — these endpoints are intentionally open so
 * any device that scans a QR or taps an NFC tag can resolve the card.
 *
 * Cards that are not yet published, no longer active, or past their
 * expiry date return 404 to keep the surface tight.
 */
class PublicCardController extends Controller
{
    /**
     * GET /cards/{public_url}
     *
     * Fetch a single published, active, non-expired card.
     */
    public function show(string $publicUrl)
    {
        $card = $this->resolveLiveCard($publicUrl, ['template']);

        if (! $card) {
            return ResponseHelper::error(
                __('messages.business_card_unavailable'),
                null,
                404
            );
        }

        // While the employee is working on the next version the live row sits
        // in draft, so serve the frozen copy of what the owner actually
        // approved rather than the work in progress.
        if ($card->status !== BusinessCard::STATUS_PUBLISHED) {
            $card->applyPublishedSnapshot();
        }

        // PublicCardResource, NOT BusinessCardResource: this route is
        // unauthenticated and the internal resource emits card_data_json
        // verbatim, iqama_number and all.
        return ResponseHelper::success(
            new PublicCardResource($card),
            __('messages.data_retrieved')
        );
    }

    /**
     * The availability gate every public read/write shares: live, switched on,
     * and not past its expiry. Anything else is a 404 to the outside world.
     *
     * "Live" includes a card whose employee has reopened it for another round
     * of edits — it was published once, the snapshot is still being served, and
     * taking the URL down mid-edit would break every QR already in circulation.
     */
    private function resolveLiveCard(string $publicUrl, array $with = []): ?BusinessCard
    {
        $card = BusinessCard::with($with)
            ->where('public_url', $publicUrl)
            ->first();

        if (! $card || ! $card->isPubliclyVisible()) {
            return null;
        }

        return $card;
    }

    /**
     * POST /cards/{public_url}/contact
     *
     * Reverse contact exchange — a visitor with no account and no app sends
     * their own details back to the card holder, who sees them in the mobile
     * app and gets a notification.
     *
     * Write-only by design: there is no public read of the collected rows, and
     * the response echoes nothing the caller did not already supply.
     */
    public function shareContact(Request $request, string $publicUrl)
    {
        $card = $this->resolveLiveCard($publicUrl, ['employee.user', 'employee.company']);

        if (! $card) {
            return ResponseHelper::error(
                __('messages.business_card_unavailable'),
                null,
                404
            );
        }

        $employeeName = $card->employee?->name;

        // Honeypot: a field no human ever sees, so anything in it is a bot.
        // Answered with the same envelope a real submission gets — a 4xx would
        // only teach the bot which field to leave alone next time.
        if (filled($request->input('website'))) {
            return ResponseHelper::success(
                ['id' => null, 'already_shared' => false, 'employee_name' => $employeeName],
                __('messages.contact_shared')
            );
        }

        // Plain validate() on purpose: the public web form and the mobile client
        // both expect Laravel's standard {message, errors} 422 body.
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name'  => ['required', 'string', 'max:80'],
            // rfc, not dns — the visitor is standing in front of the card holder
            // and a slow or failing DNS lookup must never block the exchange.
            'email'      => ['required', 'email:rfc', 'max:190'],
            'phone'      => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-]{7,25}$/'],
            'note'       => ['nullable', 'string', 'max:280'],
            'source'     => ['nullable', 'in:QR,NFC,LINK'],
            'consent'    => ['required', 'accepted'],
        ]);

        // Lower-cased on the way in so the (business_card_id, email) unique index
        // behaves the same on case-sensitive SQLite and case-insensitive MySQL.
        $email = Str::lower(trim($validated['email']));

        $share = CardContactShare::withTrashed()->updateOrCreate(
            [
                'business_card_id' => $card->id,
                'email'            => $email,
            ],
            [
                'employee_id' => $card->employee_id,
                'company_id'  => $card->employee?->company_id,
                'first_name'  => $validated['first_name'],
                'last_name'   => $validated['last_name'],
                'phone'       => $validated['phone'] ?? null,
                'note'        => $validated['note'] ?? null,
                'source'      => $validated['source'] ?? 'LINK',
                'consent_at'  => now(),
                'ip_address'  => $request->ip(),
                'user_agent'  => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );

        // Someone the employee previously removed and who shares again is a
        // returning contact, not a unique-index collision — hence withTrashed()
        // above plus an explicit restore here.
        if ($share->trashed()) {
            $share->restore();
        }

        // At most one push per sender per day, so a visitor fumbling the form
        // does not rattle the card holder's phone repeatedly.
        $shouldNotify = $share->wasRecentlyCreated
            || ! $share->notified_at
            || $share->notified_at->lt(now()->subDay());

        if ($shouldNotify) {
            (new NotificationService())->notifyUser(
                $card->employee?->user,
                __('messages.notif_contact_received_title'),
                __('messages.notif_contact_received_body', ['name' => $share->full_name]),
                [
                    'type'         => 'contact_received',
                    'card_id'      => $card->id,
                    'share_id'     => $share->id,
                    'contact_name' => $share->full_name,
                ]
            );

            // Stamped unconditionally: notifyUser returns void and swallows its
            // own failures, so this records "we tried" — which is all the
            // once-a-day guard needs to know.
            $share->forceFill(['notified_at' => now()])->save();
        }

        return ResponseHelper::success(
            [
                'id'             => $share->id,
                'already_shared' => ! $share->wasRecentlyCreated,
                'employee_name'  => $employeeName,
            ],
            __('messages.contact_shared')
        );
    }

    /**
     * POST /cards/{public_url}/track
     *
     * Record a public interaction (view / qr_scan / nfc_scan / click).
     * Best-effort: silently ignored if the card is unavailable so the public
     * page can fire-and-forget without checking the response.
     */
    public function track(Request $request, string $publicUrl)
    {
        $card = BusinessCard::where('public_url', $publicUrl)->first();

        if (! $card) {
            return ResponseHelper::success(null, __('messages.data_saved'));
        }

        $validated = $request->validate([
            'interaction_type' => ['required', 'string', 'max:50'],
            'source'           => ['nullable', 'string', 'max:50'],
        ]);

        // card_interactions.source is NOT NULL in the schema — a request
        // without source used to 500 on insert. Default it instead.
        $validated['source'] = $validated['source'] ?? 'LINK';

        $card->interactions()->create($validated);

        return ResponseHelper::success(null, __('messages.data_saved'));
    }
}
