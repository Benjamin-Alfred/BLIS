<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAliasColumnToTestTypeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	
		 DB::statement('ALTER TABLE test_types ADD COLUMN alias VARCHAR(100) AFTER name);');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		 DB::statement('ALTER TABLE test_types DROP COLUMN alias;');
	}

}
