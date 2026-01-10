<?php

use App\Models\Product;
use App\Models\Server;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_proxmox_nodes', function (Blueprint $table) {
            $table->foreignIdFor(Location::class, 'location_id')
                ->nullable()
                ->constrained();
            // 0 is no limit
            $table->integer('memory')->default(0);
            $table->integer('disk')->default(0);
            $table->integer('cpu')->default(0);
            $table->string('storage_location')->default('local');
            $table->string('backup_location')->default('local');
        });

        $server_id = Server::where('extension', 'Proxmox')->get()->first();
        Node::all()->each(function (Node $node) use ($server_id) {
            // Create a new group
            $group = Location::create([
                'name' => $node->name,
                'server_placement' => 'default',
            ]);

            // Assign the node to the group
            $node->location_id = $group->id;
            $node->save();

            if (!$server_id) {
                return;
            }
            Product::where('server_id', $server_id->id)->get()->each(function (Product $product) use ($group, $node) {
                if (
                    $product->settings()->where('key', 'location_id')->exists() ||
                    $product->settings()->where('key', 'node')->get()->first()->value === $node->id
                ) {
                    return;
                }
                $product->settings()->create([
                    'key' => 'location_id',
                    'value' => $group->id,
                ]);
            });
        });

        Schema::table('ext_proxmox_nodes', function (Blueprint $table) {
            // Make non-nullable if there are no null values
            $table->foreignIdFor(Location::class, 'location_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_nodes', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'memory', 'disk', 'cpu', 'storage_location', 'backup_location']);
        });

        // Remove all locations
        Location::truncate();
    }
};
