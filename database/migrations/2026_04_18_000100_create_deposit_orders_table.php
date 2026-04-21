<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deposit_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending');
            $table->string('provider')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deposit_orders');
    }
};
