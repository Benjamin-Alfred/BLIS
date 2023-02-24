<?php
namespace KBLIS\Plugins;

class Vitek2Machine extends \KBLIS\Instrumentation\AbstractInstrumentor
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
			'code' => 'VK2', 
			'name' => 'Vitek 2 Analyzer', 
			'description' => 'Vitek 2 microbiology analyzer performs culture and identification tests with outstanding ease-of-use, accuracy and reliability.',
			'testTypes' => \Config::get('kblis.amr-test-name-aliases')
			);
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

		#
		#   Get results output, sanitize the output,
		#   insert results into an array for handling in front end
		#

		$results = [];
		$JSONString = "{";

		$xml = simplexml_load_file($this->SOURCE_FILE_URI);

		if($xml){
			$results['specimen_id'] = $xml->requestResult->testOrder->specimen->specimenIdentifier;
			$JSONString .= '"specimen_id":"'.$results['specimen_id'].'",';

			$results['patient_id_vitek'] = $xml->requestResult->patientInformation->patientIdentifier;
			$JSONString .= '"patient_id_vitek":"'.$results['patient_id_vitek'].'",';

			$results['patient_name_vitek'] = $xml->requestResult->patientInformation->lastName;
			$JSONString .= '"patient_name_vitek":"'.$results['patient_name_vitek'].'",';

			$results['test_name'] = $xml->requestResult->testOrder->test->universalIdentifier->testName;
			$JSONString .= '"test_name":"'.$results['test_name'].'",';

			$results['identification'] = $xml->requestResult->testOrder->test->result->value->identification->significantTaxon->name;
			$JSONString .= '"identification":"'.$results['identification'].'","ast":[';

			$antibiotics = array();

			try {		
				foreach ($xml->requestResult->testOrder->test->result->value->ast->children() as $antibiotic) {
					array_push($antibiotics, array(
						"name" => $antibiotic->name,
						"mic_sign" => $antibiotic->highMic->micSign,
						"mic_value" => $antibiotic->highMic->micValue,
						"category" => $antibiotic->category
					));

					$JSONString .= '{"name":"'.$antibiotic->name.'",';
					$JSONString .= '"mic":"'.$antibiotic->highMic->micSign.$antibiotic->highMic->micValue.'",';
					$JSONString .= '"category":"'.$antibiotic->category.'"},';

				}
			} catch (\Exception $e) {
				\Log::error($e);
			}
			$results['ast'] = $antibiotics;

			$JSONString = substr($JSONString, 0, -1)."]}";
		}else{
			\Log::error("Not XML File?");
		}

		return $JSONString;
	}

}
