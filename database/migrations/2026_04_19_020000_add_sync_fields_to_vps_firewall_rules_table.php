<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_firewall_rules', function (Blueprint $table) {
            $table->string('sync_status', 20)->default('pending')->after('source_range');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('vps_firewall_rules', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'sync_error', 'synced_at']);
        });
    }
};
