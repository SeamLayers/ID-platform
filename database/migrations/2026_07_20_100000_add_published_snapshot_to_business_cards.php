<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an employee re-edit a card that is already live.
 *
 * Until now `published` was a one-way door: the card became read-only for the
 * employee forever, because letting them edit the live row would push
 * unreviewed changes straight onto the public URL. The snapshot breaks that
 * trade-off — publishing freezes a copy of exactly what went live, the public
 * page renders the snapshot, and the live row is free to go back to draft for
 * another round of review without the card going dark.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->json('published_snapshot')->nullable()->after('customized_at');
            $table->timestamp('published_at')->nullable()->after('published_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('business_cards', function (Blueprint $table) {
            $table->dropColumn(['published_snapshot', 'published_at']);
        });
    }
};
