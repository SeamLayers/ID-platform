<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */



    public function up(): void
    {
        Schema::create('business_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('business_card_templates');

            $table->json('card_data_json')->nullable();

            $table->string('qr_code')->nullable();
            $table->string('nfc_code')->nullable();
            $table->string('public_url')->unique();
            $table->date('expiry_public_url')->nullable();

            $table->boolean('is_active')->default(true);


            $table->string('status')->default('draft');
            // draft, submitted, approved, rejected, published

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_cards');
    }
};
