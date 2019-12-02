<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAliasColumnToTestTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('test_types', function($table)
		{
		    $table->string('alias',150)->nullable()->after('name');
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
			$table->dropColumn('alias');
		});
	}

}
