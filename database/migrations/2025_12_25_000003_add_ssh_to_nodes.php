<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_proxmox_nodes', function (Blueprint $table) {
            $table->string('ssh_host')->nullable()->after('vnc_enabled');
            $table->integer('ssh_port')->default(22)->after('ssh_host');
            $table->text('ssh_password')->nullable()->after('ssh_port');
        });
    }

    public function down(): void
    {
        Schema::table('ext_proxmox_nodes', function (Blueprint $table) {
            $table->dropColumn(['ssh_host', 'ssh_port', 'ssh_password']);
        });
    }
};
