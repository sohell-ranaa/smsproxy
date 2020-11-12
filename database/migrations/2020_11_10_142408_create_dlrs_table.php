<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDlrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dlrs', function (Blueprint $table) {
            $table->id();
            $table->string('to')->nullable();
            $table->unsignedBigInteger('sms_id')->nullable();
            $table->index('sms_id');
            $table->string('from')->default('eksShop');
            $table->string('delivered_data')->nullable();
            $table->string('msg_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dlrs');
    }
}
