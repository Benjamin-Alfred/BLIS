<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutomatedToTestTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('test_types', function($table)
		{
		    $table->boolean('is_automated')->nullable()->after('accredited');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('test_types', function(Blueprint $table)
		{
			$table->dropColumn('is_automated');
		});
	}

}
