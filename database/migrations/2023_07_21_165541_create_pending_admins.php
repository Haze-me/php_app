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
        Schema::create('pending_admins', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('email')->unique();
            $table->unsignedBigInteger('sub_channel_id')->nullable();
            $table->unsignedBigInteger('channel_id');
            $table->foreign('sub_channel_id')
                  ->references('id')->on('sub_channels');
            $table->foreign('channel_id')
                  ->references('id')->on('channels');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_admins');
    }
};
