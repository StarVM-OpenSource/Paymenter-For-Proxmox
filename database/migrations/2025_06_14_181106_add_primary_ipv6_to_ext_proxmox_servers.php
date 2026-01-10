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
            $table->dropForeign('ext_proxmox_servers_primary_ip_foreign');
            $table->unsignedBigInteger('primary_ip')->nullable()->change();

            // Add new foreign key constraint
            $table->foreign('primary_ip')
                ->references('id')
                ->on('ext_proxmox_ip_addresses')
                ->nullOnDelete();
            $table->renameColumn('primary_ip', 'primary_ipv4');
            $table->foreignIdFor(IPAddress::class, 'primary_ipv6')->after('primary_ipv4')->nullable()->constrained('ext_proxmox_ip_addresses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_servers', function (Blueprint $table) {
            $table->dropForeign(['primary_ipv6']);
            $table->dropColumn('primary_ipv6');
            $table->renameColumn('primary_ipv4', 'primary_ip');
        });
    }
};
