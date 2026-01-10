<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_proxmox_port_forwards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('ext_proxmox_servers')->onDelete('cascade');
            $table->foreignId('ip_pool_id')->constrained('ext_proxmox_ip_pools')->onDelete('cascade');
            $table->string('protocol')->default('tcp'); // tcp / udp / both
            $table->integer('external_port');
            $table->integer('internal_port');
            $table->string('internal_ip');
            $table->string('description')->nullable();
            $table->boolean('is_range')->default(false);
            $table->integer('range_size')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['ip_pool_id', 'protocol', 'external_port'], 'port_forward_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_proxmox_port_forwards');
    }
};
