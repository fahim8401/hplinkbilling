f<?php

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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->string('gateway_transaction_id')->nullable()->after('transaction_id');
            $table->string('gateway_customer_id')->nullable()->after('gateway_transaction_id');
            $table->string('gateway_mobile_no')->nullable()->after('gateway_customer_id');
            $table->string('gateway_datetime')->nullable()->after('gateway_mobile_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'gateway_transaction_id',
                'gateway_customer_id',
                'gateway_mobile_no',
                'gateway_datetime'
            ]);
        });
    }
};