<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;
use Paymenter\Extensions\Servers\Proxmox\Models\OS;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_proxmox_servers', function (Blueprint $table) {
            $table->id();
            $table->string('vm_id');
            $table->foreignIdFor(Node::class, 'node_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(OS::class, 'os_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Service::class, 'service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_proxmox_servers');
    }
};
