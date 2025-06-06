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
        Schema::create(
            'impersonation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('impersonator_id')->constrained('users');
                $table->foreignId('user_id')->constrained('users');
                $table->uuid('token_id');
                $table->timestamp('expires_at');
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
