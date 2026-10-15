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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date')->index();
            $table->unsignedSmallInteger('nomor');
            $table->string('nama', 100);
            $table->string('asal', 120)->nullable();
            $table->string('kategori', 40)->index();
            $table->string('keperluan', 60);
            $table->string('jenis_kelamin', 20)->nullable();
            $table->unsignedSmallInteger('jumlah')->default(1);
            $table->string('whatsapp', 20)->nullable();
            $table->string('foto')->nullable();
            $table->string('sumber', 10)->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['visit_date', 'nomor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
