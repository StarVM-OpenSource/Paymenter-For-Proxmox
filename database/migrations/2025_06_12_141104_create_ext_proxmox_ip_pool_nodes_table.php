<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\IPPool;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_proxmox_ip_pool_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(IPPool::class, 'ip_pool_id')
                ->constrained('ext_proxmox_ip_pools')
                ->cascadeOnDelete();
            $table->foreignIdFor(Node::class, 'node_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_proxmox_ip_pool_nodes');
    }
};
