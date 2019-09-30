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
        if(Config::get('kblis.send-to-HMIS') == true){
            $this->createJsonString($testId);
            //Sending all pending requests also
            $pendingRequests = ExternalDump::where('result_returned', 2)->get();
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
        $resultString = trim($resultString, ",")."]";

        $jsonResponseString = sprintf('{"lab_number": "%s","requesting_clinician": "%s", "result": %s, "tested_by": "%s", "tested_at": "%s", "verified_by": "%s", "verified_at": "%s", "technician_comment": "%s", "specimen_name": "%s", "specimen_id": "%s"}', 
            $labNumber, $externalRequest['requesting_clinician'], $resultString, $testedBy, $testedAt, $verifiedBy, $verifiedAt trim($interpretation), $specimenName, $specimenID);

        $this->sendRequest($jsonResponseString, $labNumber);
        
        Log::info($httpCurl);
        curl_close($httpCurl);
    }

    /**
    *   Function to send Json request using Curl
    **/

    private function sendRequest($jsonResponse, $labNumber)
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
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)],
                ];
        curl_setopt_array($httpCurl, $defaults);

        $response = curl_exec($httpCurl);

        Log::info("RESPONSE: ".$response);

        //"Test updated" is the actual response 
        //TODO: Replace true with actual expected response this is just for testing
        if($response == "Test updated")
        {
            //Set status in external lab-request to `sent`
            $updatedExternalRequest = ExternalDump::where('lab_number', '=', $labNumber)->first();
            $updatedExternalRequest->result_returned = 1;
            $updatedExternalRequest->save();
        }
        else
        {
            //Set status in external lab-request to `sent`
            $updatedExternalRequest = ExternalDump::where('lab_number', '=', $labNumber)->first();
            $updatedExternalRequest->result_returned = 2;
            $updatedExternalRequest->save();
            Log::error("HTTP Error: FansoftInterfacer failed to send $jsonResponse : Error message "+ curl_error($httpCurl));
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
        //First: Check if patient exists, if true dont save again
        $fullName = $labRequest->patient->first_name;
        $middleName = $labRequest->patient->middle_name;
        $lastName = $labRequest->patient->last_name;
        if(isset($middleName) && strcmp($middleName, "") > 0) $fullName = "$fullName " . $middleName;
        if(isset($lastName) && strcmp($lastName, "") > 0) $fullName = "$fullName " . $lastName;

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
        }
        else{
            $patient = $patient->first();
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
        $visitType = array('ip' => 'In-patient', 'op' => 'Out-patient');//Should be a constant
        $visit = Visit::where('visit_number', '=', $labRequest->patient_visit_number)->where('visit_type', '=', $visitType[$labRequest->patient_visit_type])->where('patient_id', '=', $patient->id)->get();
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
            if(strcmp($visitType[$labRequest->patient_visit_type], $visit->visit_type) !=0)
            {
                $visit = new Visit();
                $visit->patient_id = $patient->id;
                $visit->visit_type = $visitType[$labRequest->patient_visit_type];
                $visit->visit_number = $labRequest->patient_visit_number;
            }
        }

        $test = null;
        //Check if parentLabNO is 0 thus its the main test and not a measure
        //if($labRequest->parentLabNo == '0' || $this->isPanelTest($labRequest))
        if($labRequest->parent_lab_number == '0')
        {
            //Check via the labno, if this is a duplicate request and we already saved the test

            $test = Test::where('external_id', '=', $labRequest->lab_number)->orderby('time_created', 'desc')->get();
            if (!$test->first() || $test->first()->visit->patient_id != $patient->id)
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
                return;
            }else{
                echo '{"status": "error", "message": "Duplicate investigation request!"}';
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
        $dumper = ExternalDump::firstOrNew(array('lab_no' => $labRequest->lab_number));
        $dumper->lab_no = $labRequest->lab_number;
        $dumper->parent_lab_no = $labRequest->parent_lab_number;
        if($dumper->test_id == null){
            $dumper->test_id = $testId;
        }
        else if($dumper->test_id != null && $testId != null && $dumper->test_id != $testId){
            $dumper->test_id = $testId;
        }
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