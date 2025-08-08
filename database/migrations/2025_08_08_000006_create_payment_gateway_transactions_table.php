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
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('gateway'); // bkash or nagad
            $table->string('transaction_type'); // check_bill, payment, search
            $table->string('customer_id_gateway')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('datetime')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('result')->nullable();
            $table->string('name')->nullable();
            $table->string('contact')->nullable();
            $table->string('bill_amount')->nullable();
            $table->string('paid_amount')->nullable();
            $table->string('trx_id')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('payment_id')->references('id')->on('payments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};