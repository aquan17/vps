<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vps_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('gcp_project_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->unique();
            $table->string('zone');
            $table->string('machine_type');
            $table->string('gcp_id')->nullable();
            $table->string('status')->default('PROVISIONING');
            $table->string('public_ip')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vps_instances');
    }
};
