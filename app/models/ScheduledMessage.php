<?php

class ScheduledMessage extends Eloquent
{
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'scheduled_messages';

	public $timestamps = false;

	public static function scheduleTestResponse($testID, $destinationURI = "http://microbiologyapi.nphl.go.ke/v1", $status = 0, $retries = 3, $retryInterval = 60, $timeToSend = "now()"){

		$content['payload'] = "";
		$requestRecord = ExternalDump::where('test_id', '=', $testID)->first();
		$location = explode("|", $requestRecord->city);
		$county = count($location)>0?$location[0]:"Bungoma";
		$subCounty = count($location)>1?$location[1]:"";
		$ward = count($location)>2?$location[2]:"";
		$village = count($location)>3?$location[3]:"";
		$test = Test::find($testID);

		$AMRTests = Config::get('kblis.amr-test-name-aliases');

		if($test && in_array(strtoupper($test->testType->name), $AMRTests)){
			$testContent = [];
			$testContent['patient_number'] = $test->visit->visit_number;
			$testContent['gender'] = $test->visit->patient->getGender();
			$testContent['age'] = $test->visit->patient->getAge("Y");
			$testContent['age_unit'] = "years";
			$testContent['county'] = $county;
			$testContent['sub_county'] = $subCounty;
			$testContent['village'] = $village;
			$testContent['prediagnosis'] = $requestRecord->provisional_diagnosis;
			$testContent['specimen_collection_date'] = $test->specimen->time_accepted;
			$testContent['patient_type'] = $requestRecord->patient_visit_type;
			$testContent['ward'] = strcmp($requestRecord->patient_visit_type, "IP") == 0?$ward:"";
			$testContent['admission_date'] = strcmp($requestRecord->patient_visit_type, "IP") == 0?$requestRecord->admission_date:"";
			$testContent['currently_on_therapy'] = $requestRecord->request_notes;
			$testContent['specimen_type'] = $test->specimen->specimenType->name;
			$testContent['specimen_source'] = Config::get('kblis.organization');
			$testContent['lab_id'] = Config::get('kblis.facility-code');

			$testContent['isolates'] = $test->getCultureIsolates();

			$testContent['test_type'] = $test->testType->name;

			$content['payload'] = $testContent;
		}

		Log::info("[ScheduledMessage] content = ".json_encode($content));

		$scheduledMessage = new ScheduledMessage();
		$scheduledMessage->payload = json_encode($content);
		$scheduledMessage->destination_uri = $destinationURI;
		$scheduledMessage->status = $status;
		$scheduledMessage->retries = $retries;
		$scheduledMessage->retry_interval = $retryInterval;
		$scheduledMessage->time_in = "now()";
		$scheduledMessage->time_to_send = $timeToSend;

		$scheduledMessage->save();
	}

	public function sendMessage(){
		//TODO - We should do this in an implementation of an interface. Not a model

		$messages = ScheduledMessage::where('status', '=', 0);

		foreach ($messages as $message) {
	        //We use curl to send the requests
	        $httpCurl = curl_init();

	        $defaults = [
	                    CURLOPT_URL => $message->destination_uri,
	                    CURLOPT_POST => true,
	                    CURLINFO_HEADER_OUT => true,
	                    CURLOPT_POSTFIELDS => $message->payload,
	                    CURLOPT_RETURNTRANSFER => true,
	                    CURLOPT_SSL_VERIFYPEER => false,
	                    CURLOPT_SSL_VERIFYHOST => 0,
	                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($message->payload)],
	                ];
	        curl_setopt_array($httpCurl, $defaults);

	        $response = curl_exec($httpCurl);

            $message->ack_message = $response;
            $message->status = 2; // success
            $message->save();
            Log::info("Response received from external system: $response for ScheduledMessage {$message->id}");

	        curl_close($httpCurl);
	    }
	}
}