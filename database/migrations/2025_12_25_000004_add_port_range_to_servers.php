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
            // 每个服务器分配的端口范围
            $table->unsignedInteger('port_range_start')->nullable()->after('hostname');
            $table->unsignedInteger('port_range_end')->nullable()->after('port_range_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->dropColumn(['port_range_start', 'port_range_end']);
        });
    }
};
