<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToTestsTableTimeCreated extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tests', function(Blueprint $table)
		{
			$table->index(array('test_status_id', 'time_created'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tests', function(Blueprint $table)
		{
			$table->dropIndex('tests_test_status_id_time_created_index');
		});
	}

}
