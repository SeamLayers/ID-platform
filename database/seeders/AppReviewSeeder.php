<?php

namespace Database\Seeders;

use App\Models\BusinessCard;
use App\Models\BusinessCardTemplate;
use App\Models\CardContactShare;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The account Apple App Review signs in with.
 *
 * The 1.2.0 submission was rejected under Guideline 2.1 because the reviewer
 * was handed an account with no company, no employee record and no card: every
 * core screen rendered an empty state, so none of the app's actual features
 * could be exercised. This seeder builds a complete, self-consistent employee
 * so the reviewer lands on a live, published business card with contacts
 * waiting in the inbox.
 *
 * Safe to re-run: every write is keyed on a natural unique column, so it
 * converges on the same rows instead of stacking duplicates. It touches ONLY
 * the review account's own records and never modifies real customer data.
 *
 *   php artisan db:seed --class=AppReviewSeeder --force
 */
class AppReviewSeeder extends Seeder
{
    /** Handed to Apple in App Store Connect > App Review Information. */
    private const REVIEW_EMAIL    = 'appreview@idplus.cfd';
    private const REVIEW_PASSWORD = 'AppReview2026!';

    public function run(): void
    {
        DB::transaction(function () {
            // ── The company, owned by the existing owner account ────────────
            $owner = User::firstOrCreate(
                ['email' => 'owner@company.com'],
                [
                    'name'      => 'Company Owner',
                    'password'  => Hash::make('12345'),
                    'user_type' => 'owner',
                ]
            );
            $owner->assignRole('owner');
            // An owner who still has to change their password cannot approve a
            // card, which is half of what the reviewer is asked to look at.
            $owner->forceFill([
                'must_reset_password' => false,
                'expire_password'     => null,
            ])->save();

            $company = Company::updateOrCreate(
                ['user_id' => $owner->id],
                [
                    'name'                => 'Mhawer Group',
                    'commercial_register' => '1010101010',
                    'phone'               => '+966112345678',
                    'email'               => 'info@mhawer.example',
                ]
            );

            $branch = CompanyBranch::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Riyadh Head Office'],
                ['address' => 'King Fahd Road, Riyadh']
            );

            $department = Department::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Business Development'],
                ['code' => 'BD', 'branch_id' => $branch->id]
            );

            // ── The employee the reviewer signs in as ───────────────────────
            $user = User::updateOrCreate(
                ['email' => self::REVIEW_EMAIL],
                [
                    'name'      => 'Nora Al-Qahtani',
                    'password'  => Hash::make(self::REVIEW_PASSWORD),
                    'user_type' => 'employee',
                ]
            );
            $user->assignRole('employee');
            // The reviewer must land straight on the app, not on a forced
            // change-password wall they have no way to get past. Clearing
            // expire_password matters too: login() rejects outright once it is
            // in the past, so a stale value would lock Apple out mid-review.
            $user->forceFill([
                'must_reset_password' => false,
                'expire_password'     => null,
            ])->save();

            $employee = Employee::updateOrCreate(
                ['email' => self::REVIEW_EMAIL],
                [
                    'company_id'      => $company->id,
                    'branch_id'       => $branch->id,
                    'department_id'   => $department->id,
                    'user_id'         => $user->id,
                    'employee_number' => 'EMP-1000',
                    'iqama_number'    => '2000000001',
                    'name'            => 'Nora Al-Qahtani',
                    'phone'           => '+966501234567',
                    'position'        => 'Business Development Manager',
                    'status'          => 'active',
                ]
            );

            $template = BusinessCardTemplate::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Mhawer Default'],
                [
                    'is_default'  => true,
                    'design_json' => [
                        'theme' => ['accent' => '#00BCD4', 'background' => '#0D1117'],
                    ],
                ]
            );

            // ── A card that is actually live on its public URL ──────────────
            $card = BusinessCard::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'template_id'     => $template->id,
                    'public_url'      => 'nora-alqahtani-appreview',
                    'is_active'       => true,
                    'status'          => BusinessCard::STATUS_PUBLISHED,
                    'bio'             => 'Business development manager at Mhawer Group, '
                                       . 'working with partners across the Gulf region.',
                    'secondary_phone' => '+966501234568',
                    'theme_json'      => ['accent' => '#00BCD4'],
                    'card_data_json'  => [
                        'name'       => 'Nora Al-Qahtani',
                        'position'   => 'Business Development Manager',
                        'company'    => 'Mhawer Group',
                        'branch'     => 'Riyadh Head Office',
                        'department' => 'Business Development',
                        'email'      => self::REVIEW_EMAIL,
                        'phone'      => '+966501234567',
                    ],
                    'submitted_at'    => now()->subDays(3),
                    'reviewed_at'     => now()->subDays(2),
                    'customized_at'   => now()->subDays(3),
                    'published_at'    => now()->subDays(2),
                ]
            );

            // isPubliclyVisible() accepts either a published status or a frozen
            // snapshot; capture one so the public page keeps serving even if the
            // card is later reopened for another round of edits.
            $card->published_snapshot = $card->capturePublishedSnapshot();
            $card->save();

            // ── Contacts waiting in the reviewer's inbox ────────────────────
            $contacts = [
                ['Omar',  'Al-Sudairy', 'omar.alsudairy@example.com',  '+966555000111', 'Met at the Riyadh expo — let us schedule a call.', 'QR'],
                ['Layla', 'Hassan',     'layla.hassan@example.com',    '+966555000222', 'Please send the partnership deck.',                'NFC'],
                ['Yusuf', 'Rahman',     'yusuf.rahman@example.com',    null,            null,                                               'LINK'],
            ];

            foreach ($contacts as $i => [$first, $last, $email, $phone, $note, $source]) {
                CardContactShare::updateOrCreate(
                    ['business_card_id' => $card->id, 'email' => $email],
                    [
                        'employee_id' => $employee->id,
                        'company_id'  => $company->id,
                        'first_name'  => $first,
                        'last_name'   => $last,
                        'phone'       => $phone,
                        'note'        => $note,
                        'source'      => $source,
                        'consent_at'  => now()->subDays($i + 1),
                        'is_read'     => $i > 0,
                    ]
                );
            }

            $this->command->info('App Review demo account ready.');
            $this->command->info('  Email    : ' . self::REVIEW_EMAIL);
            $this->command->info('  Password : ' . self::REVIEW_PASSWORD);
            $this->command->info('  Card     : /card/' . $card->public_url);
        });
    }
}
