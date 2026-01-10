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
        Schema::create('ext_proxmox_ip_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('network_interface')->default('vmbr0');
            $table->string('mtu')->default('1500');
            $table->string('vlan')->nullable();
            $table->boolean('firewall')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_proxmox_ip_pools');
    }
};
