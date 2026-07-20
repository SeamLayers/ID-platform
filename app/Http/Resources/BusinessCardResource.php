<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BusinessCardResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,

            'template' => new BusinessCardTemplateResource(
                $this->whenLoaded('template')
            ),

            'card_data_json' => $this->card_data_json,

            'qr_code' => $this->qr_code
                ? Storage::disk('public')->url($this->qr_code)
                : null,

            'nfc_code' => $this->nfc_code,

            'public_url' => url('/api/v1/card/' . $this->public_url),
            'expiry_public_url' => $this->expiry_public_url,

            'is_active' => (bool) $this->is_active,
            'status' => $this->status,

            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'reviewed_by' => $this->reviewed_by,
            'rejection_reason' => $this->rejection_reason,

            // --- Employee personalisation + the owner's review note ---------
            'bio' => $this->bio,
            'secondary_phone' => $this->secondary_phone,
            'photo' => $this->photoUrl(),
            // Only the keys the employee actually overrode; the app layers
            // these on top of template.design_json.theme.
            'theme' => $this->theme_json,
            // The colours to actually paint with: template theme first, the
            // employee's overrides on top. Saves every client re-implementing
            // the merge.
            'effective_theme' => $this->effectiveTheme(),
            'review_comment' => $this->review_comment,
            'customized_at' => $this->customized_at,
            // Whether the app should show the editor or a read-only card.
            'can_edit' => $this->isEmployeeEditable(),
            // Whether the employee can take the card back into their own hands.
            // Withdrawing a submission is only offered while the owner has not
            // ruled on it; an approved or live card can always be reopened for
            // another round, because the public URL keeps serving the snapshot.
            'can_reopen' => ! $this->isEmployeeEditable()
                && ! ($this->status === 'submitted' && $this->reviewed_at !== null),
            // Live to the public right now — stays true while a newer version
            // is being edited, since the snapshot is still what visitors see.
            'is_live' => $this->isPubliclyVisible(),
            'published_at' => $this->published_at,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
