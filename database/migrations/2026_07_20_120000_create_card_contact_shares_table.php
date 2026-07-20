<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_contact_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_card_id')->constrained()->cascadeOnDelete();
            // employee_id/company_id are denormalised copies of the card's owner
            // chain so the mobile inbox and future owner-level rollups can filter
            // without joining through business_cards on every request.
            $table->foreignId('employee_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->string('first_name', 80);
            $table->string('last_name', 80);
            // 190 keeps the (business_card_id, email) unique index inside MySQL's
            // 3072-byte InnoDB key limit under utf8mb4.
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('note', 280)->nullable();

            $table->string('source', 10)->default('LINK'); // QR | NFC | LINK

            // Reserved for the planned "Sign in with Google" upgrade path, where a
            // sender can prove the address belongs to them. 'none' until then.
            $table->string('verification', 20)->default('none'); // none | google
            $table->string('google_sub')->nullable();

            $table->timestamp('consent_at')->nullable();
            // Throttles the "someone shared their details" push to at most one per
            // sender per day, however often they re-submit the same form.
            $table->timestamp('notified_at')->nullable();
            $table->boolean('is_read')->default(false);

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 250)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // A given person occupies exactly one row per card — a re-submit
            // updates their details instead of stacking duplicates.
            $table->unique(['business_card_id', 'email']);
            $table->index(['employee_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_contact_shares');
    }
};
