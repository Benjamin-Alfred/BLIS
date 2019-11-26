<?php

class InterfacerController extends \BaseController{

    /**
    * Authenticate API calls using Secret keys set on the UI
    * @param authkey Key to check if valid
    * @return boolean True if key is valid
    */
    public function authenticate($authKey)
    {
        if($authKey == Config::get('kblis.hmis-key'))//default key for the time being 123456
        {
            return true;
        }
        return false;
    }

    public function receiveLabRequest()
    {
        //authenticate() connection

        $labRequest = Request::getContent();
        // $labRequest = str_replace(['labRequest', '='], ['', ''], $labRequest);

        if($this->authenticate(Input::get('api_key'))){
            $labRequestString =  Input::get('lab_request');
            $labRequest = json_decode($labRequestString);
            \Log::info($labRequestString);

            if (is_null($labRequest)) {
                echo '{"status": "error", "message": "Malformed JSON string!"}';
                Log::info("Malformed Json string.");
                return;
            }
        }else{
            echo '{"status": "error", "message": "Authentication failure!"}';
            \Log::info("API validation failure.");
            return;
        }
        //Validate::ifValid()

        //Fire event with the received data
        Event::fire('api.receivedLabRequest', $labRequest);
    }

    public function connect(){}
    public function disconnect(){}
    public function searchPatients(){}
    public function searchResults(){}

    /**
    * Save results of a particular test
    * @param key For authentication
    * @param testId Id of test
    * @param measureid measure of result to be saved
    * @param result result to be saved
    * @return json with success or failure
    **/
    public function saveTestResults()
    {
        //Auth
        $authKey = Input::get('key');
        if(!$this->authenticate($authKey)){
            return json_encode(array('error' => 'Authentication failed'));
        }
        //save results
        // $result = Input::get('result');
        $results = Input::get('results');
        $resultsArray = explode(", ", $results);
        foreach ($resultsArray as $key => $result) {
            $ms = explode(":", $result);
            $rs = explode("=", $ms[1]);
            $testId  = str_replace("{", "", $ms[0]);
            $measureId = $rs[0];
            $res = str_replace("}", "", $rs[1]);

            try {
                $test = Test::find($testId);
                    $testResult = TestResult::firstOrCreate(array('test_id' => $testId, 'measure_id' => $measureId));
                    //Validate results
                    $testResult->result = $res;
                    //TODO: Try catch to handle failure
                    $testResult->save();
                    $test = Test::find($testId);
                    $test->tested_by = 1;
                    $test->time_completed = date('Y-m-d H:i:s');
                    $test->save();
            }
            catch(\QueryException $qe){
                return Response::json(array('Failed'));
            }
        }
        return Response::json(array('Success'));
    }

    /**
    * Get test, specimen, measure info related to a test
    * @param key For authentication
    * @param Filters to get specific info
    * @return json of the test info
    */
    public function getTests()
    {
        //Auth
        $authKey = Input::get('key');
        if(!$this->authenticate($authKey)){
            return Response::json(array('error' => 'Authentication failed'), '403');
        }
        //Validate params
        $testType = Input::get('testtype');
        $dateFrom = Input::get('datefrom');
        $dateTo = Input::get('dateto');

        if( empty($testType))
        {
            return Response::json(array('error' => 'No test provided'), '404');
        }
        //Search by name / Date
        $testType = TestType::where('name', $testType)->first();

        if( !empty($testType) ){
            $tests = Test::with('visit.patient', 'testType.measures')
                 ->where(function($query)
                    {
                        $query->where('test_status_id', Test::STARTED);
                    })
                ->where('test_type_id', $testType->id)
                ->where('time_created', '>', $dateFrom)
                ->where('time_created', '<', $dateTo)
                ->get();
        }
        //Search by ID
        //$tests = Specimen::where('visit_id', $testFilter);
        return Response::json($tests, '200');
    }

    /**
    * Get measure info related to a test
    * @param key For authentication
    * @param testId testID to get the measure info for
    * @return json of the test info
    */
    public function getTestInfo()
    {
        $key = Input::get('key');
        $testId = Input::get('testId');
        //Auth
        $authKey = $key;
        if(!$this->authenticate($authKey)){
            return json_encode(array('error' => 'Authentication failed'));
        }
        //return test info
        $test = Test::with('testType', 'testType.measures', 'specimen.specimenType')->where('visit_id', $testId);
        return Response::json($test);
    }
}