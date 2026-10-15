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
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('foto');
            $table->string('title', 120);
            $table->string('image')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('duration', 10)->nullable();
            $table->string('poster_line1', 30)->nullable();
            $table->string('poster_line2', 30)->nullable();
            $table->string('poster_sub', 80)->nullable();
            $table->string('poster_tone', 20)->nullable();
            $table->date('taken_at')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
