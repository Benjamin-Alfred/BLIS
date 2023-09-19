<?php
namespace KBLIS\Plugins;

class Humastar200TextMachine extends \KBLIS\Instrumentation\AbstractInstrumentor
{

	protected $RESULTS_KEYS = array(
				);

	protected $DATETIME_KEYS = array(
				);

	private $SOURCE_FILE_URI = "";


	/**
	* Sets the source file URI 
	*
	* @return void 
	*/
	public function setSourceFileURI($sourceFileURI){

		$this->SOURCE_FILE_URI = $sourceFileURI;
	}

	/**
	* Returns information about an instrument 
	*
	* @return array('name' => '', 'description' => '', 'testTypes' => array()) 
	*/
	public function getEquipmentInfo(){
		return array(
			'code' => 'HS200', 
			'name' => 'Humastar 200', 
			'description' => 'Humastar 200',
			'testTypes' => \Config::get('kblis.chemistry-test-name-aliases')
			);
	}


	/**
	* Fetch Test Result from machine and format it as an array
	*
	* @return array
	*/
	public function getResult($testTypeID = 0) {

		/*
		* 1. Read result file stored on the local machine
		* 2. Parse the data
		* 3. Return an array of key-value pairs: measure_name => value
		*/

		/*-------------
		* Sample file output
		*66660;;GOT;ASAT/GOT;"23";"0";"0";U/L;Children;7/4/2023;7/4/2023 6:08:06 AM
		*66660;;GPT;ALAT/GPT;"13";"0";"0";U/L;Children;7/4/2023;7/4/2023 6:08:19 AM
		*/

		/*------------------
		* Multiple parameters are arranged each on its line
		*-------------------
		*Sample Id;Patient Id;Test;Full name;Result;Low Limit;High Limit;Units;Reference class;Collection;Result time
		*/

		#
		#   Get results output, sanitize the output,
		#   insert results into an array for handling in front end
		#

		$results = [];
		$JSONString = "{";

		$RESULTS_STRING = file_get_contents($this->SOURCE_FILE_URI);
			if ($RESULTS_STRING === FALSE){
			print "Something went wrong with getting the File";
		};

		$COMPLETE_RESULT_ARRAY = preg_split("/\r\n|\n|\r/",$RESULTS_STRING);
		$currentSampleID = "Sample Id";
		$currentResult = "";

		foreach ($COMPLETE_RESULT_ARRAY as $key => $value) {
			$segment = preg_split("/;/", $value);
			if(is_numeric($segment[0])){
					$currentResult .= "{\"sample_id\": \"$segment[0]\", \"patient_id\": \"$segment[1]\", \"measure\": \"$segment[3]\", \"result\": $segment[4]},";
			}
		}

		return json_encode("[".substr($currentResult, 0, -1)."]");
	}
}