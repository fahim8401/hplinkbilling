<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('speed')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('vat_percent', 5, 2)->default(0.00);
            $table->unsignedBigInteger('fup_limit')->nullable();
            $table->integer('duration')->default(30); // Duration in days
            $table->boolean('is_expired_package')->default(false);
            $table->unsignedBigInteger('mikrotik_profile_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packages');
    }
}