<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Susceptibility extends Eloquent
{
	protected $fillable = array('user_id', 'test_id', 'organism_id', 'drug_id');

	/**
	 * Enabling soft deletes for drug susceptibility.
	 *
	 */
	use SoftDeletingTrait;
	protected $dates = ['deleted_at'];
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'drug_susceptibility';
	/**
	 * User relationship
	 */
	public function user()
	{
	  return $this->belongsTo('User', 'user_id');
	}
	/**
	 * Test relationship
	 */
	public function test()
    {
        return $this->hasOne('Test', 'test_id');
    }
	/**
	 * Organism relationship
	 */
	public function organism()
    {
        // return $this->hasOne('Organism', 'organism_id');
        return $this->hasOne('Organism', 'id', 'organism_id');
    }
	/**
	 * Drug relationship
	 */
	public function drug()
    {
        return $this->hasOne('Drug', 'id', 'drug_id');
    }
    /*
    *	Function to return drug susceptibility given testId, organismId and drugId
    *
    */
    public static function getDrugSusceptibility($test_id, $organism_id, $drug_id){
    	$susceptibility = Susceptibility::where('test_id', $test_id)
    									->where('organism_id', $organism_id)
    									->where('drug_id', $drug_id)
    									->first();
    	return $susceptibility;
    }
}