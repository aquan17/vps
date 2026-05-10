<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->string('backup_schedule', 16)->default('off')->after('backup_enabled');
            $table->unsignedTinyInteger('backup_hour_utc')->default(0)->after('backup_schedule');
            $table->unsignedTinyInteger('backup_weekday_utc')->nullable()->after('backup_hour_utc');
            $table->unsignedSmallInteger('backup_retention_days')->default(7)->after('backup_weekday_utc');
            $table->timestamp('backup_last_run_at')->nullable()->after('backup_retention_days');
        });
    }

    public function down()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropColumn([
                'backup_schedule',
                'backup_hour_utc',
                'backup_weekday_utc',
                'backup_retention_days',
                'backup_last_run_at',
            ]);
        });
    }
};
