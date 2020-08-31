<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnitsAndRangesColumnsToTestResultsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('test_results', function($table)
		{
		    $table->string('unit',30)->nullable()->after('result');
		    $table->decimal('range_lower', 7, 3)->nullable()->after('unit');
		    $table->decimal('range_upper', 7, 3)->nullable()->after('range_lower');
		    $table->string('interpretation',100)->nullable()->after('range_upper');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('test_results', function(Blueprint $table)
		{
			$table->dropColumn('unit');
			$table->dropColumn('range_lower');
			$table->dropColumn('range_upper');
			$table->dropColumn('interpretation');
		});
	}

}
