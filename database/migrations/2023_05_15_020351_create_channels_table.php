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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('profileImage');
            $table->longtext('description');
            $table->string('type');
            $table->string('rating')->nullable();
            $table->integer('subscribers');
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->foreign('institution_id')
                  ->references('id')->on('institutions');
            $table->unsignedBigInteger('super_admin_id')  ->nullable();
            $table->foreign('super_admin_id')
                  ->references('id')->on('users');
            $table->json('sub_admins')->nullable();
            $table->json('sub_channels')->nullable();
            $table->string('topic_name')->nullable();
            $table->string('channelWebsite')->nullable();  
            $table->json('suspended_admins')->nullable(); 
            $table->json('pending_admins')->nullable();   
            $table->json('removed_admins')->nullable();
            $table->boolean('is_primary')->default(false)->nullable();
            $table->timestamps();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
