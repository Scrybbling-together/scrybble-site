<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('gumroad_subscribers', function (Blueprint $table) {
            $table->string('subscriber_id')->primary();
            $table->string('customer_hash', 64)->index();
            $table->string('product_id')->index();
            $table->string('recurrence');
            $table->string('status')->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('free_trial_ends_at')->nullable();
            $table->unsignedInteger('charge_occurrence_count')->default(0);
            $table->json('purchase_ids')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gumroad_subscribers');
    }
};
