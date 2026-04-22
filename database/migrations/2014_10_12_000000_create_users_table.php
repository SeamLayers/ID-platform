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


        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->string('phone')->nullable();
            $table->string('ip_address')->nullable();

            $table->string('user_type')->default('employee');

            $table->string('reset_otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index('user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
