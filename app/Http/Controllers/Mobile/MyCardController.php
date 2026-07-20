<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\MyCardUpdateRequest;
use App\Http\Resources\BusinessCardResource;
use App\Models\BusinessCard;
use App\Services\CardProvisioningService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * The employee's own business card, from the mobile app.
 *
 * Flow this serves:
 *   1. The owner creates the employee — a draft card is created with them.
 *   2. The employee opens the app and personalises it: photo, colours, a short
 *      bio and a second phone number.
 *   3. They submit it; the owner is notified.
 *   4. The owner approves it, or sends it back with a comment which lands here
 *      as `review_comment` for the employee to act on.
 *
 * Everything is scoped to the authenticated user's own employee record — there
 * is no id in any of these routes, so one employee can never reach another's
 * card.
 */
class MyCardController extends Controller
{
    /** Resolve the caller's own card, or null when they have none. */
    private function resolveCard(Request $request, bool $provision = false): ?BusinessCard
    {
        $employee = $request->user()?->employee;

        if (! $employee) {
            return null;
        }

        $query = fn () => BusinessCard::with(['template', 'employee.company', 'employee.department', 'employee.branch'])
            ->where('employee_id', $employee->id)
            ->first();

        $card = $query();

        // Employees created before auto-provisioning shipped have no card at
        // all, and provisioning is deliberately best-effort at creation time
        // (it logs rather than failing the employee). Either way the app used
        // to land on a 404 whose only action was a Retry that could never
        // succeed. Creating it on first open closes that dead end for good.
        if (! $card && $provision) {
            (new CardProvisioningService())->provisionFor($employee);

            // employee_id is UNIQUE, so a racing second request loses the
            // insert and simply re-reads the row the winner created.
            $card = $query();
        }

        return $card;
    }

    /**
     * GET /mobile/my-card
     *
     * The card plus the template design it inherits, so the app can render an
     * accurate preview without a second request.
     */
    public function show(Request $request)
    {
        $card = $this->resolveCard($request, provision: true);

        if (! $card) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.data_retrieved')
        );
    }

    /**
     * POST /mobile/my-card
     *
     * Save personalisation. POST rather than PUT because the photo arrives as
     * multipart and PHP does not populate $_FILES on a real PUT.
     */
    public function update(MyCardUpdateRequest $request)
    {
        $card = $this->resolveCard($request);

        if (! $card) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        // A card that is awaiting review, approved or already live is frozen —
        // otherwise an employee could silently change what the owner approved,
        // or edit a published card out from under the public URL.
        if (! $card->isEmployeeEditable()) {
            return ResponseHelper::error(__('messages.card_locked_for_editing'), null, 422);
        }

        $data = $request->validated();

        if (array_key_exists('bio', $data)) {
            $card->bio = $data['bio'];
        }

        if (array_key_exists('secondary_phone', $data)) {
            $card->secondary_phone = $data['secondary_phone'];
        }

        if (array_key_exists('theme', $data)) {
            // Merge rather than replace, so sending only { accent } keeps the
            // other colours the employee picked earlier.
            $theme = array_filter(
                array_merge($card->theme_json ?? [], $data['theme'] ?? []),
                fn ($v) => $v !== null && $v !== ''
            );
            $card->theme_json = $theme ?: null;
        }

        if ($request->boolean('remove_photo')) {
            $card->clearMediaCollection(BusinessCard::PHOTO_COLLECTION);
        } elseif ($request->hasFile('photo')) {
            $card->addMediaFromRequest('photo')
                ->toMediaCollection(BusinessCard::PHOTO_COLLECTION);
        }

        $card->customized_at = now();
        $card->save();

        return ResponseHelper::success(
            new BusinessCardResource($card->fresh(['template', 'employee.company'])),
            __('messages.data_updated')
        );
    }

    /**
     * POST /mobile/my-card/submit
     *
     * Hand the personalised card to the owner for review.
     */
    public function submit(Request $request)
    {
        $card = $this->resolveCard($request);

        if (! $card) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        if (! $card->isEmployeeEditable()) {
            return ResponseHelper::error(__('messages.card_already_submitted'), null, 422);
        }

        $card->update([
            'status'         => BusinessCard::STATUS_SUBMITTED,
            'submitted_at'   => now(),
            // Clear the previous round's feedback so the owner reviews fresh.
            'review_comment' => null,
        ]);

        $card->load('employee.company.owner');

        (new NotificationService())->notifyUser(
            $card->employee?->company?->owner,
            __('messages.notif_card_review_title'),
            __('messages.notif_card_review_body', ['name' => $card->employee?->name ?? '']),
            [
                'type'          => 'card_review_requested',
                'card_id'       => $card->id,
                'employee_name' => $card->employee?->name ?? '',
            ]
        );

        return ResponseHelper::success(
            new BusinessCardResource($card->fresh(['template', 'employee.company'])),
            __('messages.business_card_submitted')
        );
    }

    /**
     * POST /mobile/my-card/reopen
     *
     * Put an editable card back in the employee's hands. Without this, every
     * status other than draft was a one-way door: one accidental tap on "send
     * for approval" froze the editor until the owner happened to respond, and a
     * published card could never be updated again at all — so the whole screen
     * read as broken.
     *
     * The public URL is unaffected. A card that has been live keeps serving its
     * published snapshot for as long as the next version is being worked on.
     */
    public function reopen(Request $request)
    {
        $card = $this->resolveCard($request, provision: true);

        if (! $card) {
            return ResponseHelper::error(__('messages.no_card_yet'), null, 404);
        }

        if ($card->isEmployeeEditable()) {
            return ResponseHelper::error(__('messages.card_already_editable'), null, 422);
        }

        // Withdrawing is only fair play until the owner has actually looked at
        // it; after a verdict the card moves on to approved/published, which is
        // the other branch below.
        if ($card->status === BusinessCard::STATUS_SUBMITTED && $card->reviewed_at !== null) {
            return ResponseHelper::error(__('messages.card_already_reviewed'), null, 422);
        }

        // A card that is live but was published before snapshots existed has
        // nothing frozen to fall back on. Its current values ARE what the
        // public is seeing, so capture them now, before the employee changes
        // anything.
        if ($card->status === BusinessCard::STATUS_PUBLISHED && ! is_array($card->published_snapshot)) {
            $card->published_snapshot = $card->capturePublishedSnapshot();
            $card->published_at = $card->published_at ?? now();
        }

        $card->status = BusinessCard::STATUS_DRAFT;
        $card->submitted_at = null;
        $card->review_comment = null;
        $card->save();

        return ResponseHelper::success(
            new BusinessCardResource($card->fresh(['template', 'employee.company'])),
            __('messages.business_card_reopened')
        );
    }
}
