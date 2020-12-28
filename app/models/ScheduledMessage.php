<?php

class ScheduledMessage extends Eloquent
{
    	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'scheduled_messages';

	public static function scheduleTestResponse($testID, $destinationURI = "http://microbiologyapi.nphl.go.ke/v1", $status = 0, $retries = 3, $retryInterval = 60, $timeToSend = "now()"){

		$content['payload'] = "";
		$test = Test::find($testID);
		if($test){
			$testContent = [];
			$testContent['patient_number'] = $test->visit->visit_number;
			$testContent['gender'] = $test->visit->patient->getGender();
			$testContent['age'] = $test->visit->patient->getAge("Y");
			$testContent['age_unit'] = "years";
			$testContent['county'] = "";
			$testContent['sub_county'] = "";
			$testContent['village'] = "";
			$testContent['prediagnosis'] = "";
			$testContent['specimen_collection_date'] = $test->specimen->time_accepted;
			$testContent['patient_type'] = $test->visit->visit_type;
			$testContent['ward'] = "";
			$testContent['admission_date'] = "";
			$testContent['currently_on_therapy'] = "";
			$testContent['specimen_type'] = $test->specimen->specimenType->name;
			$testContent['specimen_source'] = $test->specimen->specimenType->name;
			$testContent['lab_id'] = Config::get('kblis.facility-code');

			$testContent['isolates'] = $test->getCultureIsolates();

			$testContent['test_type'] = $test->testType->name;

			$content['payload'] = $testContent;
		}

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