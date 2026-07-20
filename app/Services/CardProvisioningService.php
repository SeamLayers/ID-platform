<?php

namespace App\Services;

use App\Models\BusinessCard;
use App\Models\BusinessCardTemplate;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;

/**
 * Gives every new employee a business card straight away.
 *
 * Before this, an owner had to remember to go to Business Cards → Issue Cards
 * after creating each employee; until they did, the employee opened the app to
 * an empty profile with nothing to personalise. Now the card exists from the
 * moment the employee does, as a draft, ready for them to make their own.
 */
class CardProvisioningService
{
    public function __construct(
        private ?CardCodeService $codes = null
    ) {
        $this->codes = $codes ?: new CardCodeService();
    }

    /**
     * Create the employee's card if they don't have one yet.
     *
     * Returns the card, or null when provisioning was skipped or failed.
     * NEVER throws: employee creation must not fail because QR generation or a
     * template lookup did (the QR encoder needs the imagick extension, which
     * isn't present on every host).
     */
    public function provisionFor(Employee $employee): ?BusinessCard
    {
        try {
            // business_cards.employee_id is UNIQUE — one card per employee.
            $existing = BusinessCard::where('employee_id', $employee->id)->first();
            if ($existing) {
                return $existing;
            }

            $template = $this->resolveTemplate($employee);
            if (! $template) {
                Log::warning('Card auto-provision skipped: no template', [
                    'employee_id' => $employee->id,
                    'company_id'  => $employee->company_id,
                ]);

                return null;
            }

            $employee->loadMissing(['company', 'branch', 'department', 'role']);

            $generated = $this->codes->generateAll($employee);

            $card = BusinessCard::create([
                'employee_id' => $employee->id,
                'template_id' => $template->id,
                'card_data_json' => [
                    'employee_number' => $employee->employee_number,
                    'name'            => $employee->name,
                    'email'           => $employee->email,
                    'phone'           => $employee->phone,
                    'iqama_number'    => $employee->iqama_number,
                    'status'          => $employee->status,
                    // Included here but not by BusinessCardController@store —
                    // the job title is the one field a card can't do without.
                    'position'        => $employee->position,
                    'company'         => optional($employee->company)->name,
                    'branch'          => optional($employee->branch)->name,
                    'department'      => optional($employee->department)->name,
                    'role'            => optional($employee->role)->name,
                ],
                'public_url'        => $generated['public_url'],
                'qr_code'           => $generated['qr_code'],
                'nfc_code'          => $generated['nfc_code'],
                'expiry_public_url' => now()->addDays(365),
                'status'            => BusinessCard::STATUS_DRAFT,
            ]);

            // The first step of the whole card flow used to be silent: a card
            // was waiting to be personalised and the employee had no way to
            // know. Best-effort like every other notify — notifyUser swallows
            // its own failures — so it can't undo the card we just made.
            (new NotificationService())->notifyUser(
                $employee->user,
                __('messages.notif_card_ready_title'),
                __('messages.notif_card_ready_body'),
                ['type' => 'card_ready_to_personalise', 'card_id' => $card->id]
            );

            return $card;
        } catch (\Throwable $e) {
            // Logged, not raised: the employee (and their login) matter more
            // than the card, and the owner can still issue one by hand.
            Log::error('Card auto-provision failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The template a new card should use: the company's default, else its most
     * recent, else a starter template created on the spot.
     */
    private function resolveTemplate(Employee $employee): ?BusinessCardTemplate
    {
        if (! $employee->company_id) {
            return null;
        }

        $template = BusinessCardTemplate::where('company_id', $employee->company_id)
            ->where('is_default', true)
            ->latest('id')
            ->first();

        if ($template) {
            return $template;
        }

        $template = BusinessCardTemplate::where('company_id', $employee->company_id)
            ->latest('id')
            ->first();

        if ($template) {
            return $template;
        }

        return $this->createStarterTemplate($employee);
    }

    /**
     * A company's first template, so auto-provisioning works on day one.
     *
     * `name` is UNIQUE across the whole table (not per company), hence the id
     * suffix — two companies both called "Default" would otherwise collide.
     */
    private function createStarterTemplate(Employee $employee): ?BusinessCardTemplate
    {
        $companyName = optional($employee->company)->name ?: 'Company';

        return BusinessCardTemplate::create([
            'company_id' => $employee->company_id,
            'name'       => $companyName . ' — Default #' . $employee->company_id,
            'is_default' => true,
            'design_json' => [
                'layout' => 'modern',
                'theme'  => [
                    'background' => '#0B1220',
                    'text'       => '#FFFFFF',
                    'primary'    => '#0EA5E9',
                    'accent'     => '#22D3EE',
                ],
                'fields' => [
                    'jobTitle'   => true,
                    'department' => true,
                    'company'    => true,
                    'phone'      => true,
                    'email'      => true,
                ],
                'logo' => ['show' => true, 'position' => 'top-left', 'url' => ''],
                'qr'   => ['show' => true, 'position' => 'bottom-right'],
            ],
        ]);
    }
}
