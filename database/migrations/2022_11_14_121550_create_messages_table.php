<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiver')->references('id')->on('users')->onDelete('cascade');//Oba su user_id iz user tabele user
            $table->foreignId('sender')->references('id')->on('users')->onDelete('cascade');//
            $table->text('text');//
            $table->text('subject');//
            $table->boolean('seen')->default(false);//
            $table->boolean('important')->default(false);//
            $table->enum('status',['delivered','read','deleted']);//
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
        Schema::dropIfExists('messages');
    }
}
