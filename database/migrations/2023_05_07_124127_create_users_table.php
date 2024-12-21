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
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('username');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type');
            $table->string('provider');
            $table->string('tracking_id')->nullable();
            $table->json('channels_subscribed')->nullable();
            $table->json('subchannels_subscribed')->nullable();
            $table->json('saved_posts')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('primary_institution_id')->nullable();
            $table->string('otp')->nullable();
            $table->foreign('primary_institution_id')
                  ->references('id')->on('institutions');
            $table->boolean('reset_password')->default(false)->nullable();
            $table->string('device_token')->nullable();
            $table->timestamps();
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
