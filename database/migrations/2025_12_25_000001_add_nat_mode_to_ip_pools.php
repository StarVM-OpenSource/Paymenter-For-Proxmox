<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_proxmox_ip_pools', function (Blueprint $table) {
            $table->boolean('is_nat')->default(false)->after('firewall');
            $table->string('nat_ip')->nullable()->after('is_nat');
            $table->string('nat_interface')->nullable()->after('nat_ip');
            $table->integer('port_start')->default(10000)->after('nat_interface');
            $table->integer('port_end')->default(60000)->after('port_start');
        });
    }

    public function down(): void
    {
        Schema::table('ext_proxmox_ip_pools', function (Blueprint $table) {
            $table->dropColumn(['is_nat', 'nat_ip', 'nat_interface', 'port_start', 'port_end']);
        });
    }
};
