<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ext_proxmox_oses', function (Blueprint $table) {
            // Type: qemu (KVM VM) or lxc (Container)
            $table->string('type', 10)->default('qemu')->after('name');
            // For LXC: template path (e.g., local:vztmpl/ubuntu-22.04.tar.gz)
            $table->string('template')->nullable()->after('vm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_proxmox_oses', function (Blueprint $table) {
            $table->dropColumn(['type', 'template']);
        });
    }
};
