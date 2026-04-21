<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->index('status', 'vps_instances_status_index');
        });

        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->index('status', 'deposit_orders_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropIndex('vps_instances_status_index');
        });

        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->dropIndex('deposit_orders_status_index');
        });
    }
};
