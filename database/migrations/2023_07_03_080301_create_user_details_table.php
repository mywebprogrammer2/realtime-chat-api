<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_details', function(Blueprint $table) {
            $table->increments('id');
            $table->foreignId('users_id')->references('id')->on('users');
            $table->text('image_url')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender',['Male', 'Female', 'Other'])->nullable();
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
		Schema::drop('user_details');
	}
};
