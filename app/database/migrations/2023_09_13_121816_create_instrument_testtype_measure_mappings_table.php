<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstrumentTesttypeMeasureMappingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('instrument_testtype_measure_mappings', function (Blueprint $table){
			$table->increments('id')->unsigned();
			$table->integer('instrument_id')->unsigned();
			$table->integer('testtype_id')->unsigned();
			$table->integer('measure_id')->unsigned();
			$table->string('mapping', 30);
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
		Schema::dropIfExists('instrument_testtype_measure_mappings');
	}
}
