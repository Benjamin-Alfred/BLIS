<?php

class FansoftInterfacer implements InterfacerInterface{

    public function retrieve($labRequest)
    {
        //validate input
        //Check if json
        $this->process($labRequest);
    }

    /**
    * Process Sends results back to the originating system
    *   Send is the main entry point into the interfacer
    *   We process and send the current testID and also try and resend tests that have failed to send.
    */
    public function send($testId)
    {
        //Sending current test
        Log::info("SEND TO HMIS: ".Config::get('kblis.send-to-HMIS'));
        if(Config::get('kblis.send-to-HMIS') == 1){
            $this->createJsonString($testId);
            //Sending all pending requests also
            $pendingRequests = ExternalDump::where('result_returned', 2)->get();
	}else{
	    Log::info("Sending to HMIS is disabled!");
	}
    }


    /**
    * Retrieves the results and creates a JSON string
    *
    * @param testId the id of the test to send
    * @param 
    */
    public function createJsonString($testId)
    {
        //if($comments==null or $comments==''){$comments = 'No Comments';

	    //If testID is null we cannot handle this test as we cannot know the results
	Log::info("Creating the JSON string");
        if($testId == null){
            return null;
        }

        //Get the test and results 
        $test = Test::find($testId);
        $testResults = $test->testResults;

        //Measures
        $testTypeId = $test->testType()->get()->lists('id')[0];
        $testType = TestType::find($testTypeId);

        //Get external request details
        $externRequest = ExternalDump::where('test_id', '=', $testId)->get();

        if(!($externRequest->first())){
		//Not a request we can send back
	    Log::info("No matching request from the HMIS found for test-request-id: $testId!" );
            return null;
        }else{
            $externalRequest = $externRequest->first();
        }

        $labNumber = $externalRequest['lab_no'];

        $interpretation = "";

        $interpretation = $test->interpretation;

        $specimenName = $test->specimen->specimenType->name;
        $specimenID = $test->specimen->id;

        if($test->test_status_id == Test::COMPLETED){
            $testedBy = $test->tested_by > 0 ? $test->testedBy->name : "";
            $testedAt = $test->time_completed;
            $verifiedBy = "";
            $verifiedAt = "";
        }
        elseif ($test->test_status_id == Test::VERIFIED) {
            $testedBy = $test->tested_by > 0 ? $test->testedBy->name : "";
            $testedAt = $test->time_completed;
            $verifiedBy = $test->verified_by > 0 ? $test->verifiedBy->name : "";
            $verifiedAt = $test->time_verified;
        }

        //TestedBy

        if($testedBy == null){
            $testedBy = "59";
        }

        if($verifiedBy == null){
            $verifiedBy = "59";
        }

        $resultString = "[";
        foreach($testResults as $result){
            $resultString .= '{"parameter": "'. Measure::find($result->measure_id)->name .'",';
            $resultString .= '"value": "'. $result->result . '",';
            $limits = Measure::getRangeLimits($test->visit->patient, $result->measure_id, datetime::createfromformat('Y-m-d H:i:s', $test->time_started));
            $resultString .= '"lowerLimit": "'. $limits['lower'] . '",';
            $resultString .= '"upperLimit": "'. $limits['upper'] . '",';
            $resultString .= '"unit": "'. Measure::find($result->measure_id)->unit . '"},';
        }

        $organism = "";
        $startString = "";
        $endString = "";
        $valueString = "";
        foreach($test->getCultureIsolates() as $isolate){
            if(strcmp($organism, $isolate['isolate_name']) != 0){
                if (strcmp($organism, "") != 0) {
                    $resultString .= $startString.trim($valueString, ",").$endString;
                    $startString = "";
                    $endString = "";
                    $valueString = "";
                }
                $startString .= '{"parameter": "'. $isolate['isolate_name'] .'", "value": [';

                $valueString .= '{"drug": "'.$isolate['drug'].'", "zone": "'.$isolate['zone'].'", "interpretation": "'.$isolate['interpretation'].'"},';

                $endString .= '], "lowerLimit": "", "upperLimit": "", "unit": ""},';

            }else{

                $valueString .= '{"drug": "'.$isolate['drug'].'", "zone": "'.$isolate['zone'].'", "interpretation": "'.$isolate['interpretation'].'"},';
            }

            $organism = $isolate['isolate_name'];
        }

        $resultString .= $startString.trim($valueString, ",").$endString;

        $resultString = trim($resultString, ",")."]";

        $jsonResponseString = sprintf('{"lab_number": "%s","test_name": "%s","patient_number":"%s","requesting_clinician": "%s", "result": %s, "tested_by": "%s", "tested_at": "%s", "verified_by": "%s", "verified_at": "%s", "technician_comment": "%s", "specimen_name": "%s", "specimen_id": "%s"}', 
            $labNumber, $externalRequest['investigation'], $externalRequest['patient_id'], $externalRequest['requesting_clinician'], $resultString, $testedBy, $testedAt, $verifiedBy, $verifiedAt, trim($interpretation), $specimenName, $specimenID);

        Log::info("Attempting to send results to Fansoft: \n$jsonResponseString");

        $this->sendRequest($jsonResponseString, $testId);
        
    }

    /**
    *   Function to send Json request using Curl
    **/

    private function sendRequest($jsonResponse, $testId)
    {
        //We use curl to send the requests
        $httpCurl = curl_init();

        $params = json_encode(["lab_result" => $jsonResponse]);

        $defaults = [
                    CURLOPT_URL => Config::get('kblis.hmis-url'),
                    CURLOPT_POST => true,
                    CURLINFO_HEADER_OUT => true,
                    CURLOPT_POSTFIELDS => $params,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($params)],
                ];
        curl_setopt_array($httpCurl, $defaults);

        $response = curl_exec($httpCurl);

        //"Test updated" is the actual response 
        if(stripos($response, "200") !== false)
        {
            //Set status in external lab-request to `sent`
            $updatedExternalRequest = ExternalDump::where('test_id', '=', $testId)->first();
            $updatedExternalRequest->result_returned = 1;
            $updatedExternalRequest->save();
            Log::info("Success response received from HMIS: $response");
        }
        else
        {
            //Set status in external lab-request to `sent`
            $updatedExternalRequest = ExternalDump::where('lab_no', '=', $labNumber)->first();
            $updatedExternalRequest->result_returned = 2;
            $updatedExternalRequest->save();
            Log::error("HTTP Error: Did not receive a suitable response from Fansoft: $response");
            Log::error("Error message: " . curl_error($httpCurl));
        }

        curl_close($httpCurl);
    }

     /**
     * Function for processing the requests we receive from the external system
     * and putting the data into our system.
     *
     * @var array lab_requests
     */

    public function process($labRequest)
    {
        Log::info("Fansoft test request received:\n".json_encode($labRequest));
        //First: Check if patient exists, if true dont save again
        $fullName = $labRequest->patient->first_name;
        $middleName = $labRequest->patient->middle_name;
        $lastName = $labRequest->patient->last_name;
        if(isset($middleName) && strcmp($middleName, "") > 0) $fullName = "$fullName " . $middleName;
        if(isset($lastName) && strcmp($lastName, "") > 0) $fullName = "$fullName " . $lastName;
        $fullName = trim(str_replace("  ", " ", $fullName));

        $patient = Patient::where('external_patient_number', '=', $labRequest->patient->id)->where('name', '=', $fullName)->get();

        if (!$patient->first())
        {
            $patient = new Patient();
            $patient->external_patient_number = $labRequest->patient->id;
            $patient->patient_number = $labRequest->patient->id;
            $patient->name = $fullName;
            $gender = array('M' => Patient::MALE, 'F' => Patient::FEMALE); 
            
            $patient->gender = $gender[$labRequest->patient->gender];

            $patient->dob = $labRequest->patient->date_of_birth;
            $patient->address = $labRequest->address->address;
            $patient->phone_number = $labRequest->address->phone_number;
            $patient->created_by = User::EXTERNAL_SYSTEM_USER;
            $patient->save();

            Log::info("New patient: $fullName ".$labRequest->patient->id);
        }
        else{
            $patient = $patient->first();
            Log::info("Existing patient found: ".$labRequest->patient->id);
        }

        //We check if the test exists in our system if not we just save the request in stagingTable
        // if($labRequest->parentLabNo == '0' || $this->isPanelTest($labRequest))
        if($labRequest->parent_lab_number == '0')
        {
            $testTypeId = TestType::getTestTypeIdByTestName($labRequest->investigation);
        }
        else {
            $testTypeId = null;
        }
        if(is_null($testTypeId) && $labRequest->parent_lab_number == '0')
        {
    	    Log::error("Lab Test NOT FOUND: $labRequest->investigation");
            $this->saveToExternalDump($labRequest, ExternalDump::TEST_NOT_FOUND);
            echo '{"status": "error", "message": "Investigation not found!"}';
            return;
        }
        //Check if visit exists, if true dont save again
        $labRequest->patient_visit_type = strtolower($labRequest->patient_visit_type);
        $visitType = array('ip' => 'In-patient', 'op' => 'Out-patient');//Should be a constant
        $visit = Visit::where('visit_number', '=', $labRequest->patient_visit_number)
                    ->where('visit_type', '=', $visitType[$labRequest->patient_visit_type])
                    ->where('patient_id', '=', $patient->id)->get();
        if (!$visit->first())
        {
            $visit = new Visit();
            $visit->patient_id = $patient->id;
            $visit->visit_type = $visitType[$labRequest->patient_visit_type];
            $visit->visit_number = $labRequest->patient_visit_number;

            // We'll save Visit in a transaction a little bit below
        }
        else{
            $visit = $visit->first();
        }

        $tests = null;
        //Check if parentLabNO is 0 thus its the main test and not a measure
        if($labRequest->parent_lab_number == '0')
        {
            //Check via the labno, if this is a duplicate request and we already saved the test

            $tests = Test::where('external_id', '=', $labRequest->lab_number)
                    ->where('test_type_id', '=', $testTypeId)
                    ->orderby('time_created', 'desc')->get();
            if (!$tests->first() || ($tests->first()->visit->patient_id != $patient->id ))
            {
                //Specimen
                $specimen = new Specimen();
                $specimen->specimen_type_id = TestType::find($testTypeId)->specimenTypes->lists('id')[0];

                // We'll save the Specimen in a transaction a little bit below
                $test = new Test();
                $test->test_type_id = $testTypeId;
                $test->test_status_id = Test::NOT_RECEIVED;
                $test->created_by = User::EXTERNAL_SYSTEM_USER; //Created by external system 0
                $test->requested_by = $labRequest->requesting_clinician;
                $test->external_id = $labRequest->lab_number;

                DB::transaction(function() use ($visit, $specimen, $test) {
                    $visit->save();
                    $specimen->save();
                    $test->visit_id = $visit->id;
                    $test->specimen_id = $specimen->id;
                    $test->save();
                });

                $this->saveToExternalDump($labRequest, $test->id);
                echo '{"status": "success", "message": '.$test->id.'}';
                Log::info("Test received successfully " .$test-id."!");
            }else{
                echo '{"status": "error", "message": "Duplicate investigation request!"}';
                Log::info("Duplicate investigation request");
            }
        }
        $this->saveToExternalDump($labRequest, null);
    }

    /**
    * Function for saving the data to externalDump table
    * 
    * @param $labrequest the labrequest in array format
    * @param $testId the testID to save with the labRequest or 0 if we do not have the test
    *        in our systems.
    */
    public function saveToExternalDump($labRequest, $testId)
    {
        //Dumping all the received requests to stagingTable
        $dumper = ExternalDump::firstOrNew(array('lab_no' => $labRequest->lab_number, 'test_id' => $testId));
	$dumper->lab_no = $labRequest->lab_number;
	$dumper->test_id = $testId;
        $dumper->parent_lab_no = $labRequest->parent_lab_number;
        $dumper->requesting_clinician = $labRequest->requesting_clinician;
        $dumper->investigation = $labRequest->investigation;
        $dumper->provisional_diagnosis = '';
        $dumper->request_date = $labRequest->request_date;
        $dumper->order_stage = $labRequest->patient_visit_type;
        $dumper->patient_visit_number = $labRequest->patient_visit_number;
        $dumper->patient_id = $labRequest->patient->id;
        $dumper->full_name = $labRequest->patient->first_name . " " .$labRequest->patient->middle_name . " " . $labRequest->patient->last_name;
        $dumper->dob = $labRequest->patient->date_of_birth;
        $dumper->gender = $labRequest->patient->gender;
        $dumper->address = $labRequest->address->address;
        $dumper->postal_code = '';
        $dumper->phone_number = $labRequest->address->phone_number;
        $dumper->city = $labRequest->address->city;
        $dumper->cost = $labRequest->cost;
        $dumper->receipt_number = $labRequest->receipt_number;
        $dumper->receipt_type = $labRequest->receipt_type;
        $dumper->waiver_no = '';
        $dumper->system_id = "fansoftbg";
        $dumper->save();
    }
}
