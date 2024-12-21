<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sub_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('profileImage')->nullable();
            $table->longtext('description');
            $table->string('type');
            $table->string('category');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')
                  ->references('id')->on('users');
            $table->string('targetAudience')->nullable();
            $table->integer('subscribers')->default(0);
            $table->string('subchannelWebsite')->nullable();
            $table->unsignedBigInteger('primary_institution_id')->nullable();
            $table->foreign('primary_institution_id')
                ->references('id')->on('institutions');
            $table->integer('status')->default(0)->comment('1:active|0:pending|2:suspended');
            $table->boolean('deleted')->default(false);
            $table->string('topic_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_channels');
    }
};
