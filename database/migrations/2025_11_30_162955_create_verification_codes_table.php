<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();

            $table->string('phone_number');
            $table->string('country_code')->nullable();
            $table->string('code'); // OTP code
            $table->timestamp('expiration_date'); // when OTP expires

            $table->boolean('is_used')->default(false); // optional

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};

