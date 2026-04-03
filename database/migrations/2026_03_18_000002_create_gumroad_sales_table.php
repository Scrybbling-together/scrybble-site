<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('gumroad_sales', function (Blueprint $table) {
            $table->string('sale_id')->primary();
            $table->string('subscription_id')->nullable();
            $table->foreign('subscription_id')->references('subscriber_id')->on('gumroad_subscribers')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unsignedInteger('price');
            $table->string('subscription_duration')->nullable();
            $table->json('variants')->nullable();
            $table->string('referrer')->nullable();
            $table->boolean('paid')->default(false);
            $table->boolean('cancelled')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('partially_refunded')->default(false);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gumroad_sales');
    }
};