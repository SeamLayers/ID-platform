<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications carried a title and a message and nothing else, so
 * NotificationService's $data argument (['type' => 'card_approved',
 * 'card_id' => N]) reached push notifications but was dropped on the way to the
 * database — GET /notifications could not tell the client WHAT a notification
 * was about, and the app had no way to deep-link from the list to the card.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type', 60)->nullable()->after('message');
            $table->json('data')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['type', 'data']);
        });
    }
};
