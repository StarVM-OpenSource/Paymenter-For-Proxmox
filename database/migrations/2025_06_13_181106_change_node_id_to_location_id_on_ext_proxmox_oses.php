<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;
use Paymenter\Extensions\Servers\Proxmox\Models\OS;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_proxmox_oses', function (Blueprint $table) {
            $table->foreignIdFor(Location::class, 'location_id')
                ->nullable()
                ->constrained();
        });

        Node::all()->each(function (Node $node) {
            $location = $node->location()->first();
            if (!$location) {
                return;
            }
            OS::where('node_id', $node->id)
                ->update(['location_id' => $location->id]);
        });

        Schema::table('ext_proxmox_oses', function (Blueprint $table) {
            // Make non-nullable if there are no null values
            $table->foreignIdFor(Location::class, 'location_id')->change();
            // Drop the old node_id column
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_oses', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
