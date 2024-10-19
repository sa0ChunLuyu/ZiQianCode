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
        Schema::create('quick_databases', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('database', 50)->index();
            $table->string('auth', 200)->default('[]');
            $table->string('or_auth', 200)->default('[]');
            $table->longText('options');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_databases');
    }
};
