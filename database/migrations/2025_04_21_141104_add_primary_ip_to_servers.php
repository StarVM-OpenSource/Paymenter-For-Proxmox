<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\IPAddress;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->foreignIdFor(IPAddress::class, 'primary_ip')->after('service_id')->constrained('ext_proxmox_ipv4')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->dropForeign(['primary_ip']);
        });
    }
};
