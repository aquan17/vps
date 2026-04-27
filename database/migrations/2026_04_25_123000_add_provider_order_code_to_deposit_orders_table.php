<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_order_code')
                ->nullable()
                ->after('provider')
                ->index();
        });
    }

    public function down()
    {
        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->dropIndex(['provider_order_code']);
            $table->dropColumn('provider_order_code');
        });
    }
};
