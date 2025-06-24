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
            $table->string('session_id')->nullable()->after('remember_token');
            $table->timestamp('last_activity')->nullable()->after('session_id');
            $table->ipAddress('last_ip')->nullable()->after('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'last_activity', 'last_ip']);
        });
    }
};
