<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnsentSmsDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unsent_sms_details', function (Blueprint $table) {
            $table->id();
            $table->string('receiver_number')->nullable();
            $table->string('msg_guid')->nullable();
            $table->string('tMsgId')->nullable();
            $table->string('msg_body')->nullable();
            $table->string('msg_client')->nullable();
            $table->string('msg_provider')->nullable();
            $table->string('telecom_operator')->nullable();
            $table->integer('error_code')->nullable();
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
        Schema::dropIfExists('unsent_sms_details');
    }
}
