<?php

namespace App\Services\Wallet;

use App\Models\BusinessCard;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Builds the "Add to Apple Wallet" pass for one business card.
 *
 * The pass is a `generic` pass — the Wallet type meant for membership and ID
 * cards, which is what a business card is: a name, a role, an organisation, and
 * a code the other person scans. Scanning the QR opens the same public card
 * page the printed QR does, so a pass in Wallet and a card in a pocket behave
 * identically.
 *
 * Nothing here is reachable until the Pass Type ID certificate is installed —
 * see config/wallet.php. [isConfigured()] is the single gate: the controller,
 * the API resources and the mobile app all ask it before offering the feature,
 * so an unconfigured deployment simply has no wallet button rather than a
 * button that fails.
 */
class AppleWalletService
{
    /**
     * Everything the signer needs is present, so a pass can actually be made.
     *
     * Checked rather than assumed because the two certificates are installed by
     * hand on the server: the difference between "not set up yet" and "broken"
     * has to be visible from the outside.
     */
    public function isConfigured(): bool
    {
        $c = config('wallet.apple');

        return filled($c['pass_type_identifier'] ?? null)
            && filled($c['team_identifier'] ?? null)
            && is_readable((string) ($c['certificate_path'] ?? ''))
            && is_readable((string) ($c['wwdr_path'] ?? ''));
    }

    /**
     * The public URL that serves this card's pass, or null when the feature is
     * not configured — which is what stops the clients advertising it.
     */
    public function passUrlFor(BusinessCard $card): ?string
    {
        if (! $this->isConfigured() || ! $card->isPubliclyVisible()) {
            return null;
        }

        return url('/api/v1/cards/' . $card->public_url . '/wallet.pkpass');
    }

    /**
     * @return string  raw `.pkpass` bytes
     *
     * @throws RuntimeException when signing or bundling fails
     */
    public function buildPass(BusinessCard $card): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Apple Wallet is not configured on this deployment.');
        }

        $config = config('wallet.apple');

        $files = $this->assets();
        $files['pass.json'] = json_encode(
            $this->passDefinition($card),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $signer = new PkPassSigner(
            (string) $config['certificate_path'],
            (string) $config['certificate_password'],
            (string) $config['wwdr_path'],
        );

        return $signer->build($files);
    }

    /**
     * The pass body. Field layout mirrors the printed card: the name is what
     * you see across the room, the role and company sit under it, and the ways
     * to reach the person are on the back where Wallet keeps them selectable
     * and copyable.
     *
     * @return array<string,mixed>
     */
    private function passDefinition(BusinessCard $card): array
    {
        $config = config('wallet.apple');
        $data = is_array($card->card_data_json) ? $card->card_data_json : [];

        $name = $this->clean($data['name'] ?? null) ?? __('messages.business_card');
        $position = $this->clean($data['position'] ?? null);
        $company = $this->clean($data['company'] ?? null);
        $department = $this->clean($data['department'] ?? null);
        $email = $this->clean($data['email'] ?? null);
        $phone = $this->clean($data['phone'] ?? null);
        $secondPhone = $this->clean($card->secondary_phone);
        $bio = $this->clean($card->bio);
        $link = url('/api/v1/card/' . $card->public_url);

        $secondary = [];
        if ($position !== null) {
            $secondary[] = [
                'key' => 'position',
                'label' => __('messages.position'),
                'value' => $position,
            ];
        }
        if ($company !== null) {
            $secondary[] = [
                'key' => 'company',
                'label' => __('messages.company'),
                'value' => $company,
            ];
        }

        $auxiliary = [];
        if ($phone !== null) {
            $auxiliary[] = [
                'key' => 'phone',
                'label' => __('messages.phone'),
                'value' => $phone,
            ];
        }
        if ($department !== null) {
            $auxiliary[] = [
                'key' => 'department',
                'label' => __('messages.department'),
                'value' => $department,
            ];
        }

        // Back fields are the ones a recipient actually acts on. Wallet makes
        // a detected phone number or address tappable, so these carry the
        // contact details rather than repeating the name.
        $back = [];
        if ($email !== null) {
            $back[] = ['key' => 'backEmail', 'label' => __('messages.email'), 'value' => $email];
        }
        if ($phone !== null) {
            $back[] = ['key' => 'backPhone', 'label' => __('messages.phone'), 'value' => $phone];
        }
        if ($secondPhone !== null) {
            $back[] = [
                'key' => 'backPhone2',
                'label' => __('messages.secondary_phone'),
                'value' => $secondPhone,
            ];
        }
        if ($bio !== null) {
            $back[] = ['key' => 'backBio', 'label' => __('messages.bio'), 'value' => $bio];
        }
        $back[] = ['key' => 'backLink', 'label' => __('messages.card_link'), 'value' => $link];

        $theme = $card->effectiveTheme();

        return [
            'formatVersion' => 1,
            'passTypeIdentifier' => $config['pass_type_identifier'],
            'teamIdentifier' => $config['team_identifier'],
            'organizationName' => $company ?? $config['organization_name'],

            // Stable across rebuilds and unguessable: re-adding a card replaces
            // the pass already in Wallet instead of stacking a second copy.
            'serialNumber' => (string) $card->public_url,

            'description' => trim($name . ' — ' . ($company ?? $config['organization_name'])),

            'backgroundColor' => $this->rgb($theme['background'] ?? null)
                ?? $config['background_color'],
            'foregroundColor' => $this->rgb($theme['text'] ?? null)
                ?? $config['foreground_color'],
            'labelColor' => $this->rgb($theme['accent'] ?? $theme['primary'] ?? null)
                ?? $config['label_color'],

            'logoText' => $company ?? $config['organization_name'],

            // The same payload the printed QR carries, so a scanner cannot
            // tell which one it read.
            'barcodes' => [[
                'format' => 'PKBarcodeFormatQR',
                'message' => $link,
                'messageEncoding' => 'iso-8859-1',
                'altText' => $name,
            ]],

            'generic' => [
                'primaryFields' => [[
                    'key' => 'name',
                    'label' => __('messages.name'),
                    'value' => $name,
                ]],
                'secondaryFields' => $secondary,
                'auxiliaryFields' => $auxiliary,
                'backFields' => $back,
            ],
        ];
    }

    /**
     * Images bundled into every pass, read from resources/wallet.
     *
     * @return array<string,string>
     */
    private function assets(): array
    {
        $dir = rtrim((string) config('wallet.apple.assets_path'), '/');
        $files = [];

        foreach (['icon.png', 'icon@2x.png', 'icon@3x.png', 'logo.png', 'logo@2x.png'] as $name) {
            $path = $dir . '/' . $name;
            if (is_readable($path)) {
                $files[$name] = (string) file_get_contents($path);
            }
        }

        if (! isset($files['icon.png'])) {
            throw new RuntimeException("No icon.png in {$dir} — iOS rejects a pass without one.");
        }

        return $files;
    }

    /**
     * Wallet takes colours as `rgb(r,g,b)`, and the card themes store `#RRGGBB`
     * (or `#RGB`). Anything else returns null so the configured default wins
     * rather than a malformed value reaching the phone.
     */
    private function rgb(?string $hex): ?string
    {
        if ($hex === null) {
            return null;
        }

        $value = ltrim(trim($hex), '#');

        if (strlen($value) === 3 && ctype_xdigit($value)) {
            $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
        }

        if (strlen($value) !== 6 || ! ctype_xdigit($value)) {
            return null;
        }

        return sprintf(
            'rgb(%d,%d,%d)',
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2))
        );
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /** Logs the reason a pass could not be built, without leaking it publicly. */
    public function logFailure(BusinessCard $card, \Throwable $e): void
    {
        Log::error('Apple Wallet pass build failed', [
            'card' => $card->id,
            'error' => $e->getMessage(),
        ]);
    }
}
