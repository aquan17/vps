<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gcp_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique();
            $table->string('credentials_file');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_full')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gcp_projects');
    }
};
