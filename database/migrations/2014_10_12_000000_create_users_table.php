<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('img_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('country')->nullable();
            $table->string('device')->nullable();
            $table->string('agent')->nullable();
            $table->boolean('blocked')->nullable();
            $table->text('description')->nullable();
            $table->text('phone_nr')->nullable();
            $table->boolean('verified')->nullable();
            $table->boolean('accepted')->nullable();
            $table->enum('role',['admin','worker','boss'])->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
