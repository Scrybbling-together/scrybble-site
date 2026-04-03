<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('gumroad_sales', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->index('subscription_id');
        });
    }

    public function down()
    {
        Schema::table('gumroad_sales', function (Blueprint $table) {
            $table->dropIndex(['subscription_id']);
            $table->foreign('subscription_id')->references('subscriber_id')->on('gumroad_subscribers')->nullOnDelete();
        });
    }
};
