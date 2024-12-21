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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('sub_channel_id')->nullable();
            $table->unsignedBigInteger('poster_id')->nullable();
            $table->foreign('channel_id')
                  ->references('id')->on('channels');
            $table->foreign('sub_channel_id')
                  ->references('id')->on('sub_channels');
            $table->foreign('poster_id')
                  ->references('id')->on('users');
            $table->string('post_title')->nullable();
            $table->longText('post_body')->nullable();
            $table->json('post_images')->nullable();
            $table->string('viewType');
            $table->integer('count_view')->default(0)->nullable();
            $table->json('users_viewed')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
