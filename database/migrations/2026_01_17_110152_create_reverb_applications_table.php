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
        Schema::create('reverb_applications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('key')->unique();
            $table->string('secret');
            $table->string('name');
            $table->json('allowed_origins')->default('["*"]');
            $table->boolean('enable_client_messages')->default(false);
            $table->unsignedInteger('max_connections')->nullable();
            $table->unsignedInteger('max_message_size')->default(10000);
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverb_applications');
    }
};
