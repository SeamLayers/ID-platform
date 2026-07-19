<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee self-service card personalisation.
 *
 * The card is now auto-created when the owner creates the employee, the
 * employee personalises it from the mobile app (photo, colours, bio, a second
 * phone), submits it, and the OWNER reviews — approving it or sending it back
 * with a comment. These columns carry the employee's contribution and the
 * owner's feedback; everything else about the card stays owner-authored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            // Employee-authored content.
            $table->text('bio')->nullable()->after('card_data_json');
            $table->string('secondary_phone', 30)->nullable()->after('bio');

            // Per-card colour overrides layered on top of the template's
            // design_json.theme. Kept OUT of design_json: that document belongs
            // to the template (shared by every employee) and the dashboard
            // designer re-emits it wholesale on save, which would drop
            // per-employee keys.
            $table->json('theme_json')->nullable()->after('secondary_phone');

            // The owner's "please change this" note, shown to the employee in
            // the app. Distinct from rejection_reason, which belongs to the
            // older employee-reviews-owner flow.
            $table->text('review_comment')->nullable()->after('rejection_reason');

            // Set whenever the employee saves their own changes, so the owner
            // can tell an untouched card from a personalised one.
            $table->timestamp('customized_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->dropColumn([
                'bio',
                'secondary_phone',
                'theme_json',
                'review_comment',
                'customized_at',
            ]);
        });
    }
};
