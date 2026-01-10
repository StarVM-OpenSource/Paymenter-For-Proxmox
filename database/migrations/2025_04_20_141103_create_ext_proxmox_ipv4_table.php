<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_proxmox_ipv4', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->unique();
            $table->string('gateway');
            $table->string('bridge');
            $table->string('netmask');
            $table->foreignIdFor(Server::class, 'server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Node::class, 'node_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_proxmox_ipv4');
    }
};
