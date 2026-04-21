<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vps_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('gcp_project_id')->nullable()->constrained()->onDelete('set null');
            $table->string('rule_name', 63)->unique();
            $table->string('target_tag', 63);
            $table->string('protocol', 8);
            $table->unsignedSmallInteger('port_start');
            $table->unsignedSmallInteger('port_end');
            $table->string('source_range', 64)->default('0.0.0.0/0');
            $table->timestamps();

            $table->unique(
                ['vps_instance_id', 'protocol', 'port_start', 'port_end', 'source_range'],
                'vps_firewall_unique_rule'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_firewall_rules');
    }
};

