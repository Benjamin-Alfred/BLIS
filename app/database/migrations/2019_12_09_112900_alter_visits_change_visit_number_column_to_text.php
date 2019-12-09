<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterVisitsChangeVisitNumberColumnToText extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	
		 DB::statement('ALTER TABLE visits MODIFY visit_number VARCHAR(50);');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		 DB::statement('ALTER TABLE visits MODIFY visit_number INTEGER;');
	}

}
