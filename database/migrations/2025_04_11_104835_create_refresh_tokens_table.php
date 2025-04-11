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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('access_token_id')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->json('scopes')->nullable();
            $table->string('client_id')->nullable();
            $table->timestamps();

            // Create index for faster lookups
            $table->index(['token', 'revoked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
