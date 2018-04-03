<?php

class TestResult extends Eloquent
{
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'test_results';

	public $timestamps = false;

	/**
	 * Mass assignment fields
	 */
	protected $fillable = array('test_id', 'measure_id', 'result');

	/**
	 * Test  relationship
	 */
	public function test()
	{
		return $this->belongsTo('Test');
	}

	/**
	 * Result attribute mutator
	 */
	public function getResultAttribute($val)
	{
		return htmlspecialchars($val);
	}
	/**
	 * Results Audit relationship
	 */
	public function auditResults()
	{
		return $this->hasMany('AuditResult');
	}
	/*
	*	Counts for microbiology - count organisms per specimen type
	*
	*/
	public static function microCounts($result, $specimen, $from, $to){
		$queryMicroCounts = "SELECT count(tr.id) as total 
							FROM test_results tr
								INNER JOIN tests t ON tr.test_id = t.id
								INNER JOIN test_types tt ON t.test_type_id = tt.id
								INNER JOIN test_categories tc ON tt.test_category_id = tc.id
								INNER JOIN testtype_measures tpm ON t.test_type_id=tpm.test_type_id AND tr.measure_id=tpm.measure_id
								INNER JOIN testtype_specimentypes tst ON t.test_type_id = tst.test_type_id
								INNER JOIN specimen_types st ON tst.specimen_type_id=st.id
							WHERE tc.id = ".TestCategory::getTestCatIdByName('MICROBIOLOGY')."
								AND st.name like ? AND tr.result like ? AND tr.time_entered BETWEEN ? AND ?";

		$count = DB::select($queryMicroCounts, array("'%".$specimen."%'", "'Growth of %".$result."%'", $from, $to->format('Y-m-d')));

		return $count;
	}
	/**
	* relationship between result and measure
	*/
	public function measure()
	{
		return $this->belongsTo('Measure');
	}
}
