<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A business card as seen by the anonymous public.
 *
 * BusinessCardResource is the INTERNAL view and must never be returned from an
 * unauthenticated route. It emits `card_data_json` verbatim, and that blob
 * carries the employee's iqama_number — a Saudi national ID — alongside their
 * internal employment status. The card slug is not a secret: it is the payload
 * printed into the QR code and written to the NFC tag, so anyone who scans a
 * card could read the JSON and take the ID number with it.
 *
 * Everything here is a deliberate allow-list. Add a field only after deciding
 * you are happy for it to appear on a stranger's screen.
 */
class PublicCardResource extends JsonResource
{
    /** The only card_data_json keys a visitor is allowed to see. */
    private const PUBLIC_CARD_KEYS = [
        'name',
        'position',
        'company',
        'branch',
        'department',
        'email',
        'phone',
        // On the printed card already, so no more public than the card itself.
        'employee_number',
    ];

    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'template' => new BusinessCardTemplateResource(
                $this->whenLoaded('template')
            ),

            'card_data_json' => collect($this->card_data_json ?? [])
                ->only(self::PUBLIC_CARD_KEYS)
                ->all(),

            'qr_code' => $this->qr_code
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->qr_code)
                : null,

            'public_url' => url('/api/v1/card/' . $this->public_url),

            'bio' => $this->bio,
            'secondary_phone' => $this->secondary_phone,
            'photo' => $this->photoUrl(),
            'effective_theme' => $this->effectiveTheme(),

            // Deliberately absent: iqama_number and the employment status from
            // card_data_json; nfc_code; the whole review trail (status,
            // submitted_at, reviewed_at, reviewed_by, rejection_reason,
            // review_comment, customized_at); and the can_edit / can_reopen /
            // is_active flags, which describe an internal workflow no visitor
            // has any business knowing about.
        ];
    }
}
