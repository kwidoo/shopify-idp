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
        Schema::table('users', function (Blueprint $table) {
            $table->string('shopify_id')->nullable()->index();
            $table->json('shopify_metadata')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['shopify_id']);
            $table->dropColumn('shopify_id');
            $table->dropColumn('shopify_metadata');
            $table->dropColumn('last_login_at');
        });
    }
};
