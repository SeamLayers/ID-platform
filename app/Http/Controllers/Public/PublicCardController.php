<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Resources\BusinessCardResource;
use App\Models\BusinessCard;
use Illuminate\Http\Request;

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
        $card = BusinessCard::with(['template'])
            ->where('public_url', $publicUrl)
            ->first();

        if (
            ! $card
            || ! $card->is_active
            || $card->status !== 'published'
            || ($card->expiry_public_url && $card->expiry_public_url->isPast())
        ) {
            return ResponseHelper::error(
                __('messages.business_card_unavailable'),
                null,
                404
            );
        }

        return ResponseHelper::success(
            new BusinessCardResource($card),
            __('messages.data_retrieved')
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

        $card->interactions()->create($validated);

        return ResponseHelper::success(null, __('messages.data_saved'));
    }
}
