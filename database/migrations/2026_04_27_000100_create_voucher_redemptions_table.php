<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vps_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->unsignedInteger('discount_amount');
            $table->unsignedInteger('original_amount');
            $table->unsignedInteger('final_amount');
            $table->timestamps();

            $table->index(['user_id', 'code']);
            $table->index('vps_instance_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
