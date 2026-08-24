<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\BusinessCard;
use App\Services\Wallet\AppleWalletService;
use Illuminate\Http\Response;
use Throwable;

/**
 * GET /api/v1/cards/{public_url}/wallet.pkpass
 *
 * Serves the signed Apple Wallet pass for one live card.
 *
 * Public and unauthenticated, exactly like the card page it mirrors: the person
 * adding the card to their Wallet is the one who was just handed it, and they
 * have no account. The 40-character `public_url` is the only handle — the same
 * one the QR code and the NFC tag carry — so the endpoint is no more exposed
 * than the card itself.
 *
 * The response is what makes it work: iOS shows its "Add to Apple Wallet" sheet
 * for any download served as `application/vnd.apple.pkpass`, which is why the
 * app just opens this URL rather than parsing anything.
 */
class WalletPassController extends Controller
{
    public function __construct(private readonly AppleWalletService $wallet)
    {
    }

    public function show(string $publicUrl)
    {
        $card = BusinessCard::with('template')
            ->where('public_url', $publicUrl)
            ->first();

        // Same gate as the public card page: unpublished, deactivated and
        // expired cards are 404, not 403 — the outside world learns nothing
        // about which slugs exist.
        if (! $card || ! $card->isPubliclyVisible()) {
            return ResponseHelper::error(
                __('messages.business_card_unavailable'),
                null,
                404
            );
        }

        // 503, not 500: the certificate is missing, which is a deployment step
        // that has not happened yet rather than a fault in the request.
        if (! $this->wallet->isConfigured()) {
            return ResponseHelper::error(
                __('messages.wallet_pass_unavailable'),
                null,
                503
            );
        }

        // Serve the version the public sees. A card reopened for a new round of
        // edits still has an approved snapshot online, and the pass must match
        // what the card's QR resolves to.
        if ($card->status !== BusinessCard::STATUS_PUBLISHED) {
            $card->applyPublishedSnapshot();
        }

        try {
            $pass = $this->wallet->buildPass($card);
        } catch (Throwable $e) {
            // The real reason (bad password, unreadable WWDR cert) goes to the
            // log; the caller gets the same 503 as "not set up", because from
            // outside the two are the same thing — no pass today.
            $this->wallet->logFailure($card, $e);

            return ResponseHelper::error(
                __('messages.wallet_pass_unavailable'),
                null,
                503
            );
        }

        return new Response($pass, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            // Wallet uses the filename; a generic one keeps the employee's name
            // out of the recipient's download history.
            'Content-Disposition' => 'attachment; filename="idplus-card.pkpass"',
            'Content-Length' => (string) strlen($pass),
            // A pass is regenerated from live card data on every request, so a
            // cached copy would keep serving a card the employee has changed.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
