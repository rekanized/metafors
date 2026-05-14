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
        Schema::create('metafor_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('metafor_id')->constrained()->cascadeOnDelete();
            $table->string('rater_token', 64);
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['metafor_id', 'rater_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metafor_ratings');
    }
};