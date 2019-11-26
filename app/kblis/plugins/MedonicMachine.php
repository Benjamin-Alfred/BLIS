<?php
namespace KBLIS\Plugins;
 
class MedonicMachine extends \KBLIS\Instrumentation\AbstractInstrumentor
{   

	protected $RESULTS_KEYS = array(
				'WBC',
				'UNIT-NE',
				'UNIT-LY',
				'UNIT-MO',
				'UNIT-EO',
				'UNIT-BA',
				'Neu#',
				'Lym#',
				'Mon#',
				'Eos#',
				'Baso#',
				'RBC',
				'HB',
				'HCT',
				'MCV',
				'MCH',
				'MCHC',
				'RDW',
				'PLATELET COUNT',
				'PCT',
				'MPV',
				'PDW'
				);

	protected $DATETIME_KEYS = array(
				'YEAR-CT',
				'MONTH-CT',
				'DATE-CT',
				'HH-CT',
				'MM-CT',
				'SS-CT'
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
			'code' => 'M32', 
			'name' => 'Medonic M-series', 
			'description' => 'Medonic M-series M32 hematology analyzer that delivers 22 parameter, 3-part CBCs with outstanding ease-of-use, accuracy and reliability.',
			'testTypes' => array("CBC", "WBC")
			);
	}

	private function codeSwap($code){
		$newCode = $code;
		switch ($code) {
			case 'BA%':
				$newCode = 'Baso#';
				break;
			case 'PLT':
				$newCode = 'PLATELET COUNT';
				break;
			case 'RDWR':
				$newCode = 'RDW';
				break;
			case 'HGB':
				$newCode = 'HB';
				break;
			case 'MA':
				$newCode = 'Mon#';
				break;
			case 'LA':
				$newCode = 'Lym#';
				break;
			case 'GA':
				$newCode = 'Neu#';
				break;
		}

		return $newCode;
	}

	/**
	* Fetch Test Result from machine and format it as an array
	*
	* @return array
	*/
	public function getResult($testTypeID = 0) {

		/*
		* 1. Read result file stored on the local machine (Use IP/host to verify that I'm on the correct host)
		* 2. Parse the data
		* 3. Return an array of key-value pairs: measure_name => value
		*/

		/*-------------
		* Sample file output
		*339869 6.2  17.8L 74.2*  7.2*  0.7   0.1   1.1L  4.7*  0.4*  0.0   0.0  4.26  10.5L 35.5L 83.3  24.6L 29.6L 13.0    35L 0.02L  7.0  21.3H */

		/*------------------
		* 22 Test Parameters
		*-------------------
		* WBC, LY%, MO%, NE%, EO%, BA%, LY, MO, NE, EO, BA, RBC, HGB, HCT, MCV, MCH, MCHC, RDW, PLT, PCT, MPV, PDW
		*/

		#
		#   Get results output, sanitize the output,
		#   insert results into an array for handling in front end
		#

		$medonicFile = fopen($this->SOURCE_FILE_URI, "r");
		$results = [];

		if($medonicFile !== false){
			// Output one line until end-of-file
			while(!feof($medonicFile)) {
				$line = preg_replace('/\s+/', ' ', fgets($medonicFile));

				$line = preg_replace('/\t+/', ' ', $line);
				$line = preg_replace('/\s/', ' ', $line);
				$words = explode(" ", trim($line));
				
				// if(count($words) == 2) $results[$words[0]] = $words[1];
				if(count($words) == 2) $results[$this->codeSwap($words[0])] = $words[1];
			}

		}

		return $results;
	}

}
