<?php

use App\Models\Server;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_proxmox_locations', function (Blueprint $table) {
            $table->string('host')->nullable()->after('server_placement');
            $table->string('user')->nullable()->after('host');
            $table->string('token')->nullable()->after('user');
            $table->boolean('verify_ssl')->default(false)->after('token');
        });

        $server = Server::where('extension', 'Proxmox')->first();
        if ($server) {
            Location::each(function (Location $location) use ($server) {
                $location->host = $server->settings()->where('key', 'host')->value('value');
                $location->user = $server->settings()->where('key', 'user')->value('value');
                $location->token = $server->settings()->where('key', 'token')->value('value');
                $location->verify_ssl = $server->settings()->where('key', 'verify_ssl')->value('value') ?? false;

                $location->save();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_locations', function (Blueprint $table) {
            $table->dropColumn('host');
            $table->dropColumn('user');
            $table->dropColumn('token');
            $table->dropColumn('verify_ssl');
        });
    }
};
