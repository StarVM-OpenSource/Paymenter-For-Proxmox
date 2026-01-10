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
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('user_id');
            $table->bigInteger('bandwidth_usage')->nullable()->after('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->dropColumn(['synced_at', 'bandwidth_usage']);
        });
    }
};
