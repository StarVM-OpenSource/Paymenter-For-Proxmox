<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\IPAddress;
use Paymenter\Extensions\Servers\Proxmox\Models\IPPool;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;

return new class extends Migration
{
    /**
     * Run the migrations. c899b1ae9b4480d641bb88e8bdf2024e
     */
    public function up(): void
    {
        Schema::rename('ext_proxmox_ipv4', 'ext_proxmox_ip_addresses');

        // Add mac address column
        Schema::table('ext_proxmox_ip_addresses', function (Blueprint $table) {
            $table->string('mac_address')->after('netmask')->nullable();
            $table->enum('type', ['ipv4', 'ipv6'])->default('ipv4')->after('mac_address');
            $table->foreignIdFor(IPPool::class, 'ip_pool_id')
                ->after('node_id')
                ->nullable()
                ->constrained('ext_proxmox_ip_pools');
            $table->dropColumn('bridge');
        });

        // Make ip pools for each node
        Node::all()->each(function (Node $node) {
            $ipPool = $node->ipPools()->create(['name' => $node->name . ' IP Pool']);

            // Make all IP addresses in the pool
            IPAddress::where('node_id', $node->id)
                ->update(['ip_pool_id' => $ipPool->id]);
        });

        // Add foreign key constraint for ip_pool_id
        Schema::table('ext_proxmox_ip_addresses', function (Blueprint $table) {
            // Make non-nullable if there are no null values
            $table->foreignIdFor(IPPool::class, 'ip_pool_id')->change();
            $table->dropForeign('ext_proxmox_ipv4_node_id_foreign');
            $table->dropColumn('node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_ip_addresses', function (Blueprint $table) {
            $table->dropColumn('mac_address');
            $table->dropColumn('type');
            $table->dropForeign(['ip_pool_id']);
            $table->dropColumn('ip_pool_id');
            $table->string('bridge')->after('netmask');
            $table->foreignIdFor(Node::class, 'node_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::rename('ext_proxmox_ip_addresses', 'ext_proxmox_ipv4');
    }
};
