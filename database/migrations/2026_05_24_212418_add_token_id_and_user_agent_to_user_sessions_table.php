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
        Schema::table('user_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_sessions', 'token_id')) {
                $table->unsignedBigInteger('token_id')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('user_sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('user_sessions', 'token_id')) {
                $table->dropColumn('token_id');
            }

            if (Schema::hasColumn('user_sessions', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
