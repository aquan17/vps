<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->string('voucher_code')->nullable()->after('disk');
            $table->unsignedInteger('original_price')->nullable()->after('voucher_code');
            $table->unsignedInteger('discount_amount')->default(0)->after('original_price');
            $table->unsignedInteger('paid_amount')->nullable()->after('discount_amount');
        });
    }

    public function down()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropColumn([
                'voucher_code',
                'original_price',
                'discount_amount',
                'paid_amount',
            ]);
        });
    }
};
