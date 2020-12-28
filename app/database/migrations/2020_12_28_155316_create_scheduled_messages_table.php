<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduledMessagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('scheduled_messages', function (Blueprint $table){
			$table->increments('id')->unsigned();
			$table->string('payload');
			$table->string('destination_uri');
			$table->tinyInteger('retries')->unsigned();
			$table->tinyInteger('retry_interval')->unsigned();
			$table->string('ack_message', 50);
			$table->tinyInteger('status')->unsigned();
			$table->timestamp('time_in')->nullable();
			$table->timestamp('time_to_send')->nullable();
			$table->timestamp('time_sent')->nullable();


		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('scheduled_messages');
	}
}
