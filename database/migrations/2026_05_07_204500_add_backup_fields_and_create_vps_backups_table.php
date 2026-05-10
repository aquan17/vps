<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->boolean('backup_enabled')->default(false)->after('status');
        });

        Schema::create('vps_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vps_instance_id')->constrained('vps_instances')->onDelete('cascade');
            $table->foreignId('gcp_project_id')->nullable()->constrained('gcp_projects')->onDelete('set null');
            $table->string('snapshot_name')->unique();
            $table->string('source_disk');
            $table->string('status')->default('READY');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['vps_instance_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vps_backups');

        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropColumn('backup_enabled');
        });
    }
};
