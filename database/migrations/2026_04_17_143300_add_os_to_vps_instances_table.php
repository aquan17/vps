<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->string('os')->default('ubuntu');
            $table->integer('cpu')->default(2);
            $table->integer('ram')->default(4);
            $table->integer('disk')->default(20);
        });
    }

    public function down()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropColumn(['os', 'cpu', 'ram', 'disk']);
        });
    }
};
