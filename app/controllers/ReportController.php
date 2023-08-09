<?php
set_time_limit(0); //60 seconds = 1 minute
class ReportController extends \BaseController {
	//	Begin patient report functions
	/**
	 * Display a listing of the resource.
	 * Called loadPatients because the same controller shall be used for all other reports
	 * @return Response
	 */
	public function loadPatients()
	{
		$search = Input::get('search');

		$patients = Patient::search($search)->orderBy('id','DESC')->paginate(Config::get('kblis.page-items'));

		if (count($patients) == 0) {
		 	Session::flash('message', trans('messages.no-match'));
		}

		// Load the view and pass the patients
		return View::make('reports.patient.index')->with('patients', $patients)->withInput(Input::all());
	}

	/**
	 * Display test report and its audit
	 *
	 * @return Response
	 */
	public function viewTestAuditReport($testId){

		$test = Test::find($testId);
		if(Input::has('word')){
			$date = date("Ymdhi");
			$fileName = "testauditreport_".$testId."_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.audit.exportAudit')
						->with('test', $test);
	    	return Response::make($content,200, $headers);
		}
		else{
			return View::make('reports.audit.testAudit')
						->with('test', $test);
		}
	}

	/**
	 * Display data after applying the filters on the report uses patient ID
	 *
	 * @return Response
	 */
	public function viewPatientReport($id, $visit = null, $testId = null){

		$from = Input::get('start');
		$to = Input::get('end');
		$pending = Input::get('pending');
		$date = date('Y-m-d');
		$error = '';
		$visitId = Input::has('visit_id')?Input::get('visit_id'):$visit;

		//	Check checkbox if checked and assign the 'checked' value
		if (Input::get('tests') === '1') {
		    $pending='checked';
		}
		//	Query to get tests of a particular patient
		if ($visitId && $id && $testId){
			$tests = Test::where('id', '=', $testId);
		}
		else if($visitId && $id){
			$tests = Test::where('visit_id', '=', $visitId);
		}
		else{
			$tests = Test::join('visits', 'visits.id', '=', 'tests.visit_id')
							->where('patient_id', '=', $id);
		}

		//	Begin filters - include/exclude pending tests
		if($pending){
			$tests=$tests->where('tests.test_status_id', '!=', Test::NOT_RECEIVED);
		}
		else{
			$tests = $tests->whereIn('tests.test_status_id', [Test::COMPLETED, Test::VERIFIED]);
		}

		//	Date filters
		if($from||$to){

			if(!$to) $to = $date;

			if(strtotime($from) > strtotime($to) || 
				strtotime($from) > strtotime($date) || strtotime($to) > strtotime($date)){
					$error = trans('messages.check-date-range');
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
				$tests=$tests->whereBetween('time_created', array($from, $toPlusOne->format('Y-m-d H:i:s')));
			}
		}

		//	Get tests collection
		$tests = $tests->get(array('tests.*'));
		//	Get patient details
		$patient = Patient::find($id);
		//	Check if tests are accredited
		$accredited = $this->accredited($tests);
		$verified = array();

		foreach ($tests as $test) {
			if($test->isVerified())
				array_push($verified, $test->id);
			else
				continue;
		}

		if(Input::get('adhoc')=='1'){

			return Response::json(array(
				'patient'=>$patient,
				'tests'=>$tests,
				'pending'=>$pending,
				'error'=>$error,
				'visit'=>$visit,
				'accredited'=>$accredited,
				'verified'=>$verified
			));
		}

		if(Input::has('word')){
			$date = date("Ymdhi");
			$fileName = "blispatient_".$id."_".$date.".pdf";

			$pdf = @PDF::loadView('reports.patient.export', 
					['patient' => $patient, 'tests' => $tests, 'from' => $from, 'to' => $to, 'visit' => $visit, 'accredited' => $accredited]);
			@$pdf->getDomPDF()->set_option("enable_php", true);
			@$pdf->getDomPDF()->set_option("enable_html5_parser", true);

			return @$pdf->download($fileName);
		}
		else{
			return View::make('reports.patient.report')
						->with('patient', $patient)
						->with('tests', $tests)
						->with('pending', $pending)
						->with('error', $error)
						->with('visit', $visit)
						->with('testID', $testId)
						->with('accredited', $accredited)
						->with('verified', $verified)
						->withInput(Input::all());
		}
	}
	//	End patient report functions

	/**
	*	Function to return test types of a particular test category to fill test types dropdown
	*/
	public function reportsDropdown(){
        $input = Input::get('option');
        $testCategory = TestCategory::find($input);
        $testTypes = $testCategory->testTypes();
        return Response::make($testTypes->get(['id','name']));
    }

	//	Begin Daily Log-Patient report functions
	/**
	 * Display a view of the daily patient records.
	 *
	 */
	public function dailyLog()
	{
		$from = Input::get('start');
		$to = Input::get('end');
		$pendingOrAll = Input::get('pending_or_all');
		$error = '';
		$accredited = array();
		$exportFormat = "";

		if(Input::has('word'))
			$exportFormat = Input::get('word');

		//	Check radiobutton for pending/all tests is checked and assign the 'true' value
		if (Input::get('tests') === '1') {
		    $pending='true';
		}

		$date = date('Y-m-d');
		if(!$to){
			$to=$date;
		}

		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
		$records = "tests";
		if(Input::get('records')) $records = Input::get('records');
		$testCategory = Input::get('section_id');
		$testType = Input::get('test_type');
		$labSections = TestCategory::lists('name', 'id');
		
		if($testCategory)
			$testTypes = TestCategory::find($testCategory)->testTypes->lists('name', 'id');
		else
			$testTypes = array(""=>"");
		
		if($records=='patients'){
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else{
					$visits = Visit::whereBetween('created_at', array($from, $toPlusOne))->get();
				}
				if (count($visits) == 0) {
				 	Session::flash('message', trans('messages.no-match'));
				}
			}
			else{

				$visits = Visit::where('created_at', 'LIKE', $date.'%')->orderBy('patient_id')->get();
			}
			if(Input::has('word')){
				$date = date("Ymdhi");
				$fileName = "daily_visits_log_".$date;

				$pdf = PDF::loadView('reports.daily.exportPatientLog', 
						['visits' => $visits, 'accredited' => $accredited, 'input' => Input::all()]);
				$pdf->getDomPDF()->set_option("enable_php", true);

				return $pdf->download($fileName.'.pdf');
			}
			else{
				return View::make('reports.daily.patient')
								->with('visits', $visits)
								->with('error', $error)
								->with('accredited', $accredited)
								->withInput(Input::all());
			}
		}

		//Begin specimen rejections
		if($records=='rejections')
		{
			$specimens = Specimen::where('specimen_status_id', '=', Specimen::REJECTED);
			/*Filter by test category*/
			if($testCategory&&!$testType){
				$specimens = $specimens->join('tests', 'specimens.id', '=', 'tests.specimen_id')
									   ->join('test_types', 'tests.test_type_id', '=', 'test_types.id')
									   ->where('test_types.test_category_id', '=', $testCategory);
			}
			/*Filter by test type*/
			if($testCategory&&$testType){
				$specimens = $specimens->join('tests', 'specimens.id', '=', 'tests.specimen_id')
				   					   ->where('tests.test_type_id', '=', $testType);
			}

			/*Filter by date*/
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else
				{
					$specimens = $specimens->whereBetween('time_rejected', 
						array($from, $toPlusOne))->get(array('specimens.*'));
				}
			}
			else
			{
				$specimens = $specimens->where('time_rejected', 'LIKE', $date.'%')->orderBy('id')
										->get(array('specimens.*'));
			}
			if(Input::has('word')){
				$fileName = "daily_rejected_specimen_".date("Ymdhi").".pdf";

				$pdf = PDF::loadView('reports.daily.exportSpecimenLog', 
						['specimens' => $specimens, 'testCategory' => $testCategory, 'testType' => $testType, 
							'accredited' => $accredited, 'input' => Input::all()]);

				$pdf->getDomPDF()->set_option("enable_php", true);
				$pdf->setPaper('A4', 'landscape');

				return $pdf->download($fileName);
			}
			else
			{
				return View::make('reports.daily.specimen')
							->with('labSections', $labSections)
							->with('testTypes', $testTypes)
							->with('specimens', $specimens)
							->with('testCategory', $testCategory)
							->with('testType', $testType)
							->with('error', $error)
							->with('accredited', $accredited)
							->withInput(Input::all());
			}
		}

		//Begin test records
		if($records=='tests')
		{
			$tests = Test::whereNotIn('test_status_id', [Test::NOT_RECEIVED]);
			
			/*Filter by test category*/
			if($testCategory&&!$testType){
				$tests = $tests->join('test_types', 'tests.test_type_id', '=', 'test_types.id')
							   ->where('test_types.test_category_id', '=', $testCategory);
			}
			/*Filter by test type*/
			if($testType){
				$tests = $tests->where('test_type_id', '=', $testType);
			}
			/*Filter by all tests*/
			if($pendingOrAll=='pending'){
				$tests = $tests->whereIn('test_status_id', [Test::PENDING, Test::STARTED]);
			}
			else if($pendingOrAll=='all'){
				$tests = $tests->whereIn('test_status_id', 
					[Test::PENDING, Test::STARTED, Test::COMPLETED, Test::VERIFIED]);
			}
			//For Complete tests and the default.
			else{
				$tests = $tests->whereIn('test_status_id', [Test::COMPLETED, Test::VERIFIED]);
			}
			/*Get collection of tests*/
			/*Filter by date*/
			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else
				{
					$tests = $tests->whereBetween('time_created', array($from, $toPlusOne))->get(array('tests.*'));
				}
			}
			else
			{
				$tests = $tests->where('time_created', 'LIKE', $date.'%')->get(array('tests.*'));
			}
			if(Input::has('word')){
				$date = date("Ymdhi");
				$fileName = "daily_test_records_".$date.".doc";
				$headers = array(
				    "Content-type"=>"text/html",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);
				$content = View::make('reports.daily.exportTestLog')
								->with('tests', $tests)
								->with('testCategory', $testCategory)
								->with('testType', $testType)
								->with('pendingOrAll', $pendingOrAll)
								->with('accredited', $accredited)
								->withInput(Input::all());
		    	return Response::make($content,200, $headers);
			}
			else
			{
				return View::make('reports.daily.test')
							->with('labSections', $labSections)
							->with('testTypes', $testTypes)
							->with('tests', $tests)
							->with('accredited', $this->accredited($tests))
							->with('counts', $tests->count())
							->with('testCategory', $testCategory)
							->with('testType', $testType)
							->with('pendingOrAll', $pendingOrAll)
							->with('accredited', $accredited)
							->with('error', $error)
							->withInput(Input::all());
			}
		}

		//Begin amr-test records
		if($records=='amr-tests')
		{
			//We want verified culture tests from the given date range
			/*Filter by date, test_status and test_type_id*/
			$AMRTests = Config::get('kblis.amr-test-name-aliases');

			if($from||$to){
				if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
						$error = trans('messages.check-date-range');
				}
				else
				{
					$tests = Test::getTests($AMRTests, $from, $toPlusOne->format("Y-m-d H:i:s"), [Test::VERIFIED], Test::TIME_CREATED);
				}
			}
			else
			{
				$tests = Test::getTests($AMRTests, $from, $from, [Test::VERIFIED], Test::TIME_CREATED);
			}
			
			/*Get collection of tests*/
			$content = [];

			if(count($tests) > 0){
				foreach ($tests as $test) {
					try {
						//TODO: Find a better way to accomodate new field requests
						$externalDump = ExternalDump::where('lab_no', '=', $test->external_id)->where('test_id', '=', $test->id)->get()->first();
						$preDiagnosis = $externalDump->provisional_diagnosis;
						$admissionDate = $externalDump->waiver_no;
						$location = explode("|", $externalDump->city);

						$remarks = explode("|", $externalDump->system_id);
					} catch (Exception $e) {
						Log::error($e->getFile().":".$e->getLine()." ".$e->getMessage());
						$location = [];
						$remarks = [];
						$preDiagnosis = "";
						$admissionDate = "";
					}

					$testContent = [];
					$testContent['patient_name'] = $test->visit->patient->name;
					$testContent['patient_number'] = isset($test->visit->visit_number)?$test->visit->visit_number:$test->visit->patient_id;
					$testContent['gender'] = $test->visit->patient->getGender();
					$testContent['dob'] = $test->visit->patient->dob;
					$testContent['age'] = $test->visit->patient->getAge("Y");
					$testContent['country'] = "";
					$testContent['county'] = count($location) > 3?$location[0]:'';
					$testContent['sub_county'] = count($location) > 3?$location[1]:'';
					$testContent['prediagnosis'] = $preDiagnosis;
					$testContent['specimen_collection_date'] = $test->specimen->time_accepted;
					$testContent['patient_type'] = $test->visit->visit_type;
					$testContent['ward'] = count($location) == 5?$location[4]:'';
					$testContent['admission_date'] = $admissionDate;
					$testContent['currently_on_therapy'] = count($remarks) > 1?$remarks[1]:'';
					$testContent['specimen_type'] = $test->specimen->specimenType->name;
					$testContent['specimen_source'] = $test->specimen->specimenType->name;
					$testContent['lab_id'] = $test->specimen->id;

					$testContent['isolates'] = $test->getCultureIsolates();

					$testContent['test_type'] = $test->testType->name;

					$content[] = $testContent;
				}
			}else{
				$content["message"] = ""; 
			}

			$date = date("Ymdhi");
			$fileName = "amr_whonet_report_".$date.".json";
			$fileNameXLS = public_path()."/uploads/amr_whonet_report_".$date.".xls";
			if(strcmp(strtolower(trim($exportFormat)),'json') == 0){
				$headers = array(
				    "Content-type"=>"text/json",
				    "Content-Disposition"=>"attachment;Filename=".$fileName
				);

	    		return Response::make(json_encode($content),200, $headers);
			}else if(strcmp(strtolower(trim($exportFormat)),'xls') == 0){

				$this->createAMRExportFile($fileNameXLS, $tests, $content);

				return Response::download($fileNameXLS);

			}else{

				return View::make('reports.daily.amr')
						->with('tests', $tests)
						->with('testContent', $content)
						->with('accredited', $accredited)
						->with('error', $error)
						->withInput(Input::all());
			}
		}
	}

	public function createAMRExportFile($fileName, $tests, $content){

		try{
			$amrfile = fopen($fileName, "a");
			fwrite($amrfile, "<html><body><table><thead>\n");

			$theader = "<tr>";
			$theader .= "<th>IP/OP Number</th>";
			$theader .= "<th>Gender</th>";
			$theader .= "<th>DOB</th>";
			$theader .= "<th>Age</th>";
			$theader .= "<th>Country</th>";
			$theader .= "<th>County</th>";
			$theader .= "<th>Sub-county</th>";
			$theader .= "<th>Pre-diagnosis</th>";
			$theader .= "<th>Specimen collection date</th>";
			$theader .= "<th>Location</th>";
			$theader .= "<th>Department</th>";
			$theader .= "<th>Admission Date</th>";
			$theader .= "<th>Prior Antibiotic Therapy</th>";
			$theader .= "<th>Specimen-type-title</th>";
			$theader .= "<th>Specimen Site</th>";
			$theader .= "<th>Lab ID</th>";
			$theader .= "<th>Isolates Obtained?</th>";
			$theader .= "<th>Isolate Name</th>";
			$theader .= "<th>Test Method</th>";
			$theader .= "<th>Gram Pos/Neg</th>";
			$theader .= "[ANTIBIOTIC_NAMES]";
			$theader .= "<th>Test Name</th>";
			$theader .= "</tr>";

			$antibiotics = Drug::all()->lists('name');
			$abValues = array();
			$rowSet = array();

			if (count($content) > 0) {
				foreach ($content as $tc) {
					if (count($tc) > 1) {//Wonder why it has 1 element when empty?
						$trow = "<tr>";
						$trow .= "<td>" . e($tc['patient_number']) ."</td>";
						$trow .= "<td>" . e($tc['gender']) ."</td>";
						$trow .= "<td>" . e($tc['dob']) ."</td>";
						$trow .= "<td>" . e($tc['age']) ." years</td>";
						$trow .= "<td>&nbsp;</td>";
						$trow .= "<td>" . e($tc["county"]) ."</td>";
						$trow .= "<td>" . e($tc["sub_county"]) ."</td>";
						$trow .= "<td>" . e($tc["prediagnosis"]) ."</td>";
						$trow .= "<td>".substr($tc['specimen_collection_date'],0,10) ."</td>";
						$trow .= "<td>" . e($tc['patient_type']) ."</td>";
						$trow .= "<td>" . e($tc['ward']) ."</td>";
						$trow .= "<td>" . e($tc['admission_date']) ."</td>";
						$trow .= "<td>" . e($tc['currently_on_therapy']) ."</td>";
						$trow .= "<td>" . e($tc['specimen_type']) ."</td>";
						$trow .= "<td>" . e($tc['specimen_source']) ."</td>";
						$trow .= "<td>" . e($tc['lab_id']) ."</td>";

						$isolateObtained = "";
						$isolateName = "";
						if (count($tc["isolates"]) > 0) {
							$isolateObtained .= "<p>Yes</p>";
							$isolateName = "";
							foreach ($tc["isolates"] as $suscept) {
								if(strcmp($isolateName, $suscept["isolate_name"]) != 0){
									$isolateName .= $suscept["isolate_name"];
								}
								$abValues[$tc['lab_id']][strtoupper($suscept["drug"])] = $suscept["zone"];
							}
						}else{
							$isolateObtained .= "<p>No</p>";
						}
						$trow .= "<td>".$isolateObtained."</td>";
						$trow .= "<td>" . e($isolateName)."</td>";
						$trow .= "<td>&nbsp;</td>";
						$trow .= "<td>&nbsp;</td>";
						$trow .= "[ANTIBOITIC_VALUES]";
						$trow .= "<td>" . e($tc['test_type'])."</td>";
						$trow .= "</tr>";

						$rowSet[$tc['lab_id']] = $trow;
					}
				}
			}else{
				$rowSet[] = "<tr><td colspan='22'>No records found!</td></tr>";
			}

			$abHeader = "";
			$antibiotics = array_unique($antibiotics);
			asort($antibiotics);

			foreach($antibiotics as $ab){
				$abHeader .= "<th>$ab</th>";
			}
			$theader = str_replace("[ANTIBIOTIC_NAMES]", $abHeader, $theader);

			fwrite($amrfile, $theader);

			fwrite($amrfile, "</thead><tbody>");

			foreach ($rowSet as $key => $row) {
				$abv = "";

				foreach($antibiotics as $ab){
					try{
						$abv .= "<td>" . e($abValues[$key][$ab])."</td>";
					}catch(Exception $e){
						$abv .= "<td>&nbsp;</td>";
					}
				}

				$HTMLString = str_replace("[ANTIBOITIC_VALUES]", $abv, $row);

				fwrite($amrfile, $HTMLString."\n");

			}

			fwrite($amrfile, "</tbody></table></body></html>");

			fclose($amrfile);

		}catch(Exception $e){
			Log::error($e);
		}
	}

	//	End Daily Log-Patient report functions

	/*	Begin Aggregate reports functions	*/
	//	Begin prevalence rates reports functions
	/**
	 * Display a both chart and table on load.
	 *
	 * @return Response
	 */
	public function prevalenceRates()
	{
		$from = Input::get('start');
		$to = Input::get('end');
		$today = date('Y-m-d');
		$year = date('Y');
		$testTypeID = Input::get('test_type');

		//	Apply filters if any
		if(Input::has('filter')){

			if(!$to) $to=$today;

			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($today)||strtotime($to)>strtotime($today)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$months = json_decode(self::getMonths($from, $to));
			$data = TestType::getPrevalenceCounts($from, $to, $testTypeID);
			$chart = self::getPrevalenceRatesChart($testTypeID);
		}
		else
		{
			// Get all tests for the current year
			$test = Test::where('time_created', 'LIKE', date('Y').'%');
			$periodStart = $test->min('time_created'); //Get the minimum date
			$periodEnd = $test->max('time_created'); //Get the maximum date
			$data = TestType::getPrevalenceCounts($periodStart, $periodEnd);
			$chart = self::getPrevalenceRatesChart();
		}

		return View::make('reports.prevalence.index')
						->with('data', $data)
						->with('chart', $chart)
						->withInput(Input::all());
	}

	/**
	 * Get months: return months for time_created column when filter dates are set
	 */	
	public static function getMonths($from, $to){
		$today = "'".date("Y-m-d")."'";
		$year = date('Y');
		$tests = Test::select('time_created')->distinct();

		if(strtotime($from)===strtotime($today)){
			$tests = $tests->where('time_created', 'LIKE', $year.'%');
		}
		else
		{
			$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
			$tests = $tests->whereBetween('time_created', array($from, $toPlusOne));
		}

		$allDates = $tests->lists('time_created');
		asort($allDates);
		$yearMonth = function($value){return strtotime(substr($value, 0, 7));};
		$allDates = array_map($yearMonth, $allDates);
		$allMonths = array_unique($allDates);
		$dates = array();

		foreach ($allMonths as $date) {
			$dateInfo = getdate($date);
			$dates[] = array('months' => $dateInfo['mon'], 'label' => substr($dateInfo['month'], 0, 3),
				'annum' => $dateInfo['year']);
		}

		return json_encode($dates);
	}
	/**
	 * Display prevalence rates chart
	 *
	 * @return Response
	 */
	public static function getPrevalenceRatesChart($testTypeID = 0){
		$from = Input::get('start');
		$to = Input::get('end');
		$months = json_decode(self::getMonths($from, $to));
		$testTypes = new Illuminate\Database\Eloquent\Collection();

		if($testTypeID == 0){
			
			$testTypes = TestType::supportPrevalenceCounts();
		}else{
			$testTypes->add(TestType::find($testTypeID));
		}

		$options = '{
		    "chart": {
		        "type": "spline"
		    },
		    "title": {
		        "text":"'.trans('messages.prevalence-rates').'"
		    },
		    "subtitle": {
		        "text":'; 
		        if($from==$to)
		        	$options.='"'.trans('messages.for-the-year').' '.date('Y').'"';
		        else
		        	$options.='"'.trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to.'"';
		    $options.='},
		    "credits": {
		        "enabled": false
		    },
		    "navigation": {
		        "buttonOptions": {
		            "align": "right"
		        }
		    },
		    "series": [';
		    	$counts = count($testTypes);

			    	foreach ($testTypes as $testType) {
		        		$options.= '{
		        			"name": "'.$testType->name.'","data": [';
		        				$counter = count($months);
		            			foreach ($months as $month) {
		            			$data = $testType->getPrevalenceCount($month->annum, $month->months);
		            				if($data->isEmpty()){
		            					$options.= '0.00';
		            					if($counter==1)
			            					$options.='';
			            				else
			            					$options.=',';
		            				}
		            				else{
		            					foreach ($data as $datum) {
				            				$options.= $datum->rate;

				            				if($counter==1)
				            					$options.='';
				            				else
				            					$options.=',';
					            		}
		            				}
		            			$counter--;
				    		}
				    		$options.=']';
				    	if($counts==1)
							$options.='}';
						else
							$options.='},';
						$counts--;
					}
			$options.='],
		    "xAxis": {
		        "categories": [';
		        $count = count($months);
	            	foreach ($months as $month) {
	    				$options.= '"'.$month->label." ".$month->annum;
	    				if($count==1)
	    					$options.='" ';
	    				else
	    					$options.='" ,';
	    				$count--;
	    			}
	            $options.=']
		    },
		    "yAxis": {
		        "title": {
		            "text": "'.trans('messages.prevalence-rates-label').'"
		        },
	            "min": "0",
	            "max": "100"
		    }
		}';
	return $options;
	}
	//	Begin count reports functions
	/**
	 * Display a test((un)grouped) and specimen((un)grouped) counts.
	 *
	 */
	public function countReports(){
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$to = Input::get('end');
		if(!$to) $to = $date;
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
		$counts = Input::get('counts');
		$accredited = array();
		//	Begin grouped test counts
		if($counts==trans('messages.grouped-test-counts'))
		{
			$testCategories = TestCategory::all();
			$testTypes = TestType::all();
			$ageRanges = array('0-5', '5-15', '15-120');	//	Age ranges - will definitely change in configurations
			$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female

			$perAgeRange = array();	// array for counts data for each test type and age range
			$perTestType = array();	//	array for counts data per testype
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}
			foreach ($testTypes as $testType) {
				$countAll = $this->getGroupedTestCounts($testType, null, null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countMale = $this->getGroupedTestCounts($testType, [Patient::MALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countFemale = $this->getGroupedTestCounts($testType, [Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$perTestType[$testType->id] = ['countAll'=>$countAll, 'countMale'=>$countMale, 'countFemale'=>$countFemale];
				foreach ($ageRanges as $ageRange) {
					$maleCount = $this->getGroupedTestCounts($testType, [Patient::MALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$femaleCount = $this->getGroupedTestCounts($testType, [Patient::FEMALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$perAgeRange[$testType->id][$ageRange] = ['male'=>$maleCount, 'female'=>$femaleCount];
				}
			}
			return View::make('reports.counts.groupedTestCount')
						->with('testCategories', $testCategories)
						->with('ageRanges', $ageRanges)
						->with('gender', $gender)
						->with('perTestType', $perTestType)
						->with('perAgeRange', $perAgeRange)
						->with('accredited', $accredited)
						->withInput(Input::all());
		}
		else if($counts==trans('messages.ungrouped-specimen-counts')){
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$ungroupedSpecimen = array();
			foreach (SpecimenType::all() as $specimenType) {
				$rejected = $specimenType->countPerStatus([Specimen::REJECTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$accepted = $specimenType->countPerStatus([Specimen::ACCEPTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$total = $rejected+$accepted;
				$ungroupedSpecimen[$specimenType->id] = ["total"=>$total, "rejected"=>$rejected, "accepted"=>$accepted];
			}

			// $data = $data->groupBy('test_type_id')->paginate(Config::get('kblis.page-items'));
			return View::make('reports.counts.ungroupedSpecimenCount')
							->with('ungroupedSpecimen', $ungroupedSpecimen)
							->with('accredited', $accredited)
							->withInput(Input::all());

		}
		else if($counts==trans('messages.grouped-specimen-counts')){
			$ageRanges = array('0-5', '5-15', '15-120');	//	Age ranges - will definitely change in configurations
			$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female

			$perAgeRange = array();	// array for counts data for each test type and age range
			$perSpecimenType = array();	//	array for counts data per testype
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}
			$specimenTypes = SpecimenType::all();
			foreach ($specimenTypes as $specimenType) {
				$countAll = $specimenType->groupedSpecimenCount([Patient::MALE, Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countMale = $specimenType->groupedSpecimenCount([Patient::MALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$countFemale = $specimenType->groupedSpecimenCount([Patient::FEMALE], null, $from, $toPlusOne->format('Y-m-d H:i:s'));
				$perSpecimenType[$specimenType->id] = ['countAll'=>$countAll, 'countMale'=>$countMale, 'countFemale'=>$countFemale];
				foreach ($ageRanges as $ageRange) {
					$maleCount = $specimenType->groupedSpecimenCount([Patient::MALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$femaleCount = $specimenType->groupedSpecimenCount([Patient::FEMALE], $ageRange, $from, $toPlusOne->format('Y-m-d H:i:s'));
					$perAgeRange[$specimenType->id][$ageRange] = ['male'=>$maleCount, 'female'=>$femaleCount];
				}
			}
			return View::make('reports.counts.groupedSpecimenCount')
						->with('specimenTypes', $specimenTypes)
						->with('ageRanges', $ageRanges)
						->with('gender', $gender)
						->with('perSpecimenType', $perSpecimenType)
						->with('perAgeRange', $perAgeRange)
						->with('accredited', $accredited)
						->withInput(Input::all());
		}
		else{
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
				Session::flash('message', trans('messages.check-date-range'));
			}

			$ungroupedTests = array();
			foreach (TestType::all() as $testType) {
				$pending = $testType->countPerStatus([Test::PENDING, Test::STARTED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$complete = $testType->countPerStatus([Test::COMPLETED, Test::VERIFIED], $from, $toPlusOne->format('Y-m-d H:i:s'));
				$ungroupedTests[$testType->id] = ["complete"=>$complete, "pending"=>$pending];
			}

			// $data = $data->groupBy('test_type_id')->paginate(Config::get('kblis.page-items'));
			return View::make('reports.counts.ungroupedTestCount')
							->with('ungroupedTests', $ungroupedTests)
							->with('accredited', $accredited)
							->withInput(Input::all());
		}
	}

	/*
	 *	Begin turnaround time functions - functions related to the turnaround time report
	 *	Most have been borrowed from the original BLIS by C4G
	 */
	/*
	 * 	getPercentile() returns the percentile value from the given list
	 */
	public static function getPercentile($list, $ile_value)
	{
		$num_values = count($list);
		sort($list);
		$mark = ceil(round($ile_value/100, 2) * $num_values);
		return $list[$mark-1];
	}

	/*
	 * 	week_to_date() returns timestamp for the first day of the week (Monday)
	 *	@var $week_num and $year
	 */
	public static function week_to_date($week_num, $year)
	{
		# Returns timestamp for the first day of the week (Monday)
		$week = $week_num;
		$Jan1 = mktime (0, 0, 0, 1, 1, $year); //Midnight
		$iYearFirstWeekNum = (int) strftime("%W", $Jan1);
		if ($iYearFirstWeekNum == 1)
		{
			$week = $week - 1;
		}
		$weekdayJan1 = date ('w', $Jan1);
		$FirstMonday = strtotime(((4-$weekdayJan1)%7-3) . ' days', $Jan1);
		$CurrentMondayTS = strtotime(($week) . ' weeks', $FirstMonday);
		return ($CurrentMondayTS);
	}

	/*
	 * 	rawTaT() returns list of timestamps for tests that were registered and handled between date_from and date_to
	 *	optional @var $from, $to, $labSection, $testType
	 */
	public static function rawTaT($from, $to, $labSection, $testType){
		$rawTat = DB::table('tests')->select(DB::raw('UNIX_TIMESTAMP(time_created) as timeCreated, UNIX_TIMESTAMP(time_started) as timeStarted, UNIX_TIMESTAMP(time_entered) as timeCompleted, targetTAT'))->groupBy('tests.id')
						->join('test_types', 'test_types.id', '=', 'tests.test_type_id')
						->join('test_results', 'tests.id', '=', 'test_results.test_id')
						->whereIn('test_status_id', [Test::COMPLETED, Test::VERIFIED]);
						if($from && $to){
							$rawTat = $rawTat->whereBetween('time_created', [$from, $to]);
						}
						else{
							$rawTat = $rawTat->where('time_created', 'LIKE', '%'.date("Y").'%');
						}
						if($labSection){
							$rawTat = $rawTat->where('test_category_id', $labSection);
						}
						if($testType){
							$rawTat = $rawTat->where('test_type_id', $testType);
						}
		return $rawTat->get();
	}
	/*
	 * 	getTatStats() calculates Weekly progression of TAT values for a given test type and time period
	 *	optional @var $from, $to, $labSection, $testType, $interval
	 */
	public static function getTatStats($from, $to, $labSection, $testType, $interval){
		# Calculates Weekly progression of TAT values for a given test type and time period

		$resultset = self::rawTaT($from, $to, $labSection, $testType);
		# {resultentry_ts, specimen_id, date_collected_ts, ...}

		$progression_val = array();
		$progression_count = array();
		$percentile_tofind = 90;
		$percentile_count = array();
		$goal_val = array();
		# Return {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids]]}

		if($interval == 'M'){
			foreach($resultset as $record)
			{
				$timeCreated = $record->timeCreated;
				$timeCreated_parsed = date("Y-m-d", $timeCreated);
				$timeCreated_parts = explode("-", $timeCreated_parsed);
				$month_ts = mktime(0, 0, 0, $timeCreated_parts[1], 0, $timeCreated_parts[0]);
				$month_ts_datetime = date("Y-m-d H:i:s", $month_ts);
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time

				if(!isset($progression_val[$month_ts]))
				{
					$progression_val[$month_ts] = array();
					$progression_val[$month_ts][0] = $date_diff;
					$progression_val[$month_ts][1] = $wait_diff;
					$progression_val[$month_ts][4] = array();
					$progression_val[$month_ts][4][] = $record;

					$percentile_count[$month_ts] = array();
					$percentile_count[$month_ts][] = $date_diff;

					$progression_count[$month_ts] = 1;

					if(!$record->targetTAT==null)
						$goal_tat[$month_ts] = $record->targetTAT; //Hours
					else
						$goal_tat[$month_ts] = 0.00; //Hours			
				}
				else
				{
					$progression_val[$month_ts][0] += $date_diff;
					$progression_val[$month_ts][1] += $wait_diff;
					$progression_val[$month_ts][4][] = $record;

					$percentile_count[$month_ts][] = $date_diff;

					$progression_count[$month_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		else if($interval == 'D'){
			foreach($resultset as $record)
			{
				$date_collected = $record->timeCreated;
				$day_ts = $date_collected; 
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time
				if(!isset($progression_val[$day_ts]))
				{
					$progression_val[$day_ts] = array();
					$progression_val[$day_ts][0] = $date_diff;
					$progression_val[$day_ts][1] = $wait_diff;
					$progression_val[$day_ts][4] = array();
					$progression_val[$day_ts][4][] = $record;

					$percentile_count[$day_ts] = array();
					$percentile_count[$day_ts][] = $date_diff;

					$progression_count[$day_ts] = 1;

					$goal_tat[$day_ts] = $record->targetTAT; //Hours
				}
				else
				{
					$progression_val[$day_ts][0] += $date_diff;
					$progression_val[$day_ts][1] += $wait_diff;
					$progression_val[$day_ts][4][] = $record;

					$percentile_count[$day_ts][] = $date_diff;

					$progression_count[$day_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		else{
			foreach($resultset as $record)
			{
				$date_collected = $record->timeCreated;
				$week_collected = date("W", $date_collected);
				$year_collected = date("Y", $date_collected);
				$week_ts = self::week_to_date($week_collected, $year_collected);
				$wait_diff = ($record->timeStarted - $record->timeCreated); //Waiting time
				$date_diff = ($record->timeCompleted - $record->timeStarted); //Turnaround time

				if(!isset($progression_val[$week_ts]))
				{
					$progression_val[$week_ts] = array();
					$progression_val[$week_ts][0] = $date_diff;
					$progression_val[$week_ts][1] = $wait_diff;
					$progression_val[$week_ts][4] = array();
					$progression_val[$week_ts][4][] = $record;

					$percentile_count[$week_ts] = array();
					$percentile_count[$week_ts][] = $date_diff;

					$progression_count[$week_ts] = 1;

					if(!$record->targetTAT==null)
						$goal_tat[$week_ts] = $record->targetTAT; //Hours
					else
						$goal_tat[$week_ts] = 0.00; //Hours				
				}
				else
				{
					$progression_val[$week_ts][0] += $date_diff;
					$progression_val[$week_ts][1] += $wait_diff;
					$progression_val[$week_ts][4][] = $record;

					$percentile_count[$week_ts][] = $date_diff;

					$progression_count[$week_ts] += 1;
				}
			}

			foreach($progression_val as $key=>$value)
			{
				# Find average TAT
				$progression_val[$key][0] = $value[0]/$progression_count[$key];

				# Determine percentile value
				$progression_val[$key][3] = self::getPercentile($percentile_count[$key], $percentile_tofind);

				# Convert from sec timestamp to Hours
				$progression_val[$key][0] = ($value[0]/$progression_count[$key])/(60*60);//average TAT
				$progression_val[$key][1] = ($value[1]/$progression_count[$key])/(60*60);//average WT
				$progression_val[$key][3] = $progression_val[$key][3]/(60*60);// Percentile ???

				$progression_val[$key][2] = $goal_tat[$key];

			}
		}
		# Return {month=>[avg tat, percentile tat, goal tat, [overdue specimen_ids], [pending specimen_ids], avg wait time]}
		return $progression_val;
	}

	/**
	 * turnaroundTime() function returns the turnaround time blade with necessary contents
	 *
	 * @return Response
	 */
	public function turnaroundTime()
	{
		$today = date('Y-m-d');
		$from = Input::get('start');
		$to = Input::get('end');
		if(!$to){
			$to=$today;
		}
		$testCategory = Input::get('section_id');
		$testType = Input::get('test_type');
		$labSections = TestCategory::lists('name', 'id');
		$interval = Input::get('period');
		$error = null;
		$accredited = array();
		if(!$testType)
			$error = trans('messages.select-test-type');
		if($testCategory)
			$testTypes = TestCategory::find($testCategory)->testTypes->lists('name', 'id');
		else
			$testTypes = array(""=>"");

		if($from||$to){
			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($today)||strtotime($to)>strtotime($today)){
					$error = trans('messages.check-date-range');
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
				Session::flash('fine', '');
			}
		}
		$resultset = self::getTatStats($from, $to, $testCategory, $testType, $interval);
		return View::make('reports.tat.index')
					->with('labSections', $labSections)
					->with('testTypes', $testTypes)
					->with('resultset', $resultset)
					->with('testCategory', $testCategory)
					->with('testType', $testType)
					->with('interval', $interval)
					->with('error', $error)
					->with('accredited', $accredited)
					->withInput(Input::all());
	}

	//	Begin infection reports functions
	/**
	 * Display a table containing all infection statistics.
	 *
	 */
	public function infectionReport(){

	 	$ageRanges = array('0-5'=>'Under 5 years', 
	 					'5-14'=>'5 years and over but under 14 years', 
	 					'14-120'=>'14 years and above');	//	Age ranges - will definitely change in configurations
		$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female
		$ranges = array('Low', 'Normal', 'High');
		$accredited = array();

		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));

		$testCategory = Input::get('test_category');

		$infectionData = Test::getInfectionData($from, $toPlusOne, $testCategory);	// array for counts data for each test type and age range
		
		return View::make('reports.infection.index')
					->with('gender', $gender)
					->with('ageRanges', $ageRanges)
					->with('ranges', $ranges)
					->with('infectionData', $infectionData)
					->with('accredited', $accredited)
					->withInput(Input::all());
	}

	/**
	 * Displays summary statistics on users application usage.
	 *
	 */
	public function userStatistics(){

		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$selectedUser = Input::get('user');
		if(!$selectedUser)$selectedUser = "";
		else $selectedUser = " USER: ".User::find($selectedUser)->name;

		$reportTypes = array('Summary', 'Patient Registry', 'Specimen Registry', 'Tests Registry', 'Tests Performed');

		$selectedReport = Input::get('report_type');
		if(!$selectedReport)$selectedReport = 0;

		switch ($selectedReport) {
			case '1':
				$reportData = User::getPatientsRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-patients-register-report-title',1);
				break;
			case '2':
				$reportData = User::getSpecimensRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-specimens-register-report-title',1);
				break;
			case '3':
				$reportData = User::getTestsRegistered($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-tests-register-report-title',1);
				break;
			case '4':
				$reportData = User::getTestsPerformed($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-tests-performed-report-title',1);
				break;
			default:
				$reportData = User::getSummaryUserStatistics($from, $to.' 23:59:59', Input::get('user'));
				$reportTitle = Lang::choice('messages.user-statistics-summary-report-title',1);
				break;
		}

		$reportTitle = str_replace("[FROM]", $from, $reportTitle);
		$reportTitle = str_replace("[TO]", $to, $reportTitle);
		$reportTitle = str_replace("[USER]", $selectedUser, $reportTitle);
		
		return View::make('reports.userstatistics.index')
					->with('reportTypes', $reportTypes)
					->with('reportData', $reportData)
					->with('reportTitle', $reportTitle)
					->with('selectedReport', $selectedReport)
					->withInput(Input::all());
	}

	/**
	 * Returns qc index page
	 *
	 * @return view
	 */
	public function qualityControl()
	{
		$accredited = array();
		$controls = Control::all()->lists('name', 'id');
		$accredited = array();
		$tests = array();
		return View::make('reports.qualitycontrol.index')
			->with('accredited', $accredited)
			->with('tests', $tests)
			->with('controls', $controls);
	}

	/**
	* Returns qc results for a specific control page
	*
	* @param Input - controlId, date range
	* @return view
	*/
	public function qualityControlResults()
	{
		$rules = array('start_date' => 'date|required',
					'end_date' => 'date|required',
					'control' => 'required');
		$validator = Validator::make(Input::all(), $rules);
		$accredited = array();
		if($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}
		else {
			$controlId = Input::get('control');
			$endDatePlusOne = date_add(new DateTime(Input::get('end_date')), date_interval_create_from_date_string('1 day'));
			$dates= array(Input::get('start_date'), $endDatePlusOne);
			$control = Control::find($controlId);
			$controlTests = ControlTest::where('control_id', '=', $controlId)
										->whereBetween('created_at', $dates)->get();
			$leveyJennings = $this->leveyJennings($control, $dates);
			return View::make('reports.qualitycontrol.results')
				->with('control', $control)
				->with('controlTests', $controlTests)
				->with('leveyJennings', $leveyJennings)
				->with('accredited', $accredited)
				->withInput(Input::all());
		}
	}

	/**
	 * Displays Surveillance
	 * @param string $from, string $to, array() $testTypeIds
	 * As of now surveillance works only with alphanumeric measures
	 */
	public function surveillance(){
		/*surveillance diseases*/
		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$to = Input::get('end');
		if(!$to) $to = $date;
		$accredited = array();

		$surveillance = Test::getSurveillanceData($from, $to.' 23:59:59');
		$accredited = array();
		$tests = array();

		if(Input::has('word')){
			$fileName = "surveillance_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.surveillance.exportSurveillance')
							->with('surveillance', $surveillance)
							->with('tests', $tests)
							->with('accredited', $accredited)
							->withInput(Input::all());
			return Response::make($content,200, $headers);
		}else{
			return View::make('reports.surveillance.index')
					->with('accredited', $accredited)
					->with('tests', $tests)
					->with('surveillance', $surveillance)
					->with('accredited', $accredited)
					->withInput(Input::all());
		}
	}

	/**
	 * Manage Surveillance Configurations
	 * @param
	 */
	public function surveillanceConfig(){
		
        $allSurveillanceIds = array();
		
		//edit or leave surveillance entries as is
		if (Input::get('surveillance')) {
			$diseases = Input::get('surveillance');

			foreach ($diseases as $id => $disease) {
                $allSurveillanceIds[] = $id;
				$surveillance = ReportDisease::find($id);
				$surveillance->test_type_id = $disease['test-type'];
				$surveillance->disease_id = $disease['disease'];
				$surveillance->save();
			}
		}
		
		//save new surveillance entries
		if (Input::get('new-surveillance')) {
			$diseases = Input::get('new-surveillance');

			foreach ($diseases as $id => $disease) {
				$surveillance = new ReportDisease;
				$surveillance->test_type_id = $disease['test-type'];
				$surveillance->disease_id = $disease['disease'];
				$surveillance->save();
                $allSurveillanceIds[] = $surveillance->id;
				
			}
		}

        //check if action is from a form submission
        if (Input::get('from-form')) {
	     	// Delete any pre-existing surveillance entries
	     	//that were not captured in any of the above save loops
	        $allSurveillances = ReportDisease::all(array('id'));

	        $deleteSurveillances = array();

	        //Identify survillance entries to be deleted by Ids
	        foreach ($allSurveillances as $key => $value) {
	            if (!in_array($value->id, $allSurveillanceIds)) {
	                $deleteSurveillances[] = $value->id;
	            }
	        }
	        //Delete Surveillance entry if any
	        if(count($deleteSurveillances)>0)ReportDisease::destroy($deleteSurveillances);
        }

		$diseaseTests = ReportDisease::all();

		return View::make('reportconfig.surveillance')
					->with('diseaseTests', $diseaseTests);
	}

	/**
	* Function to check object state before groupedTestCount
	**/
	public function getGroupedTestCounts($ttypeob, $gender=null, $ageRange=null, $from=null, $to=null){
		if($ttypeob == null){
			return 0;
		}
		return $ttypeob->groupedTestCount($gender, $ageRange, $from, $to);
	}
	/**
	* Function to check object state before totalTestResults
	**/
	public function getTotalTestResults($measureobj, $gender=null, $ageRange=null, $from=null, $to=null, $range=null, $positive=null){
		if($measureobj == null){
			return 0;
		}
		return $measureobj->totalTestResults($gender, $ageRange, $from, $to, $range, $positive);
	}
	/**
	 * MOH 706
	 *
	 */
	public function moh706(){

		Log::info("Start MoH 706");
		//	Variables definition

		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$end = Input::get('end');
		if(!$end) $end = $date;

		$toPlusOne = date_add(new DateTime($end), date_interval_create_from_date_string('1 day'));
		$to = date_add(new DateTime($end), date_interval_create_from_date_string('1 day'))->format('Y-m-d');

		$ageRanges = array('0-5', '5-14', '14-120');
		$sex = array(Patient::MALE, Patient::FEMALE);

		$ranges = array('Low', 'Normal', 'High');

		$specimen_types = array('Urine', 'Pus', 'HVS', 'Throat', 'Stool', 'Blood', 'CSF', 'Water', 'Food', 'Other fluids');

		$isolates = array('Naisseria', 'Klebsiella', 'Staphylococci', 'Streptoccoci'. 'Proteus', 'Shigella', 'Salmonella', 'V. cholera', 
						  'E. coli', 'C. neoformans', 'Cardinella vaginalis', 'Haemophilus', 'Bordotella pertusis', 'Pseudomonas', 
						  'Coliforms', 'Faecal coliforms', 'Enterococcus faecalis', 'Total viable counts-22C', 'Total viable counts-37C', 
						  'Clostridium', 'Others');

		//	Get specimen_types for microbiology
		$labSecId = TestCategory::getTestCatIdByName('microbiology');

		$queryMicrobiologySpecimenTypeIDs = "select distinct(specimen_types.id) as spec_id from testtype_specimentypes".
										  " join test_types on test_types.id=testtype_specimentypes.test_type_id".
										  " join specimen_types on testtype_specimentypes.specimen_type_id=specimen_types.id".
										  "  where test_types.test_category_id=?";

		$specTypeIds = DB::select(DB::raw($queryMicrobiologySpecimenTypeIDs), array($labSecId));
		Log::info("MoH 706: End fetch Microbiology specimen type IDs");

		//	Referred out specimen
		$queryReferredOutSpecimen = "SELECT specimen_type_id, specimen_types.name as spec, count(specimens.id) as tot,".
												" facility_id, facilities.name as facility FROM specimens".
												" join referrals on specimens.referral_id=referrals.id".
												" join specimen_types on specimen_type_id=specimen_types.id".
												" join facilities on referrals.facility_id=facilities.id".
												" where referral_id is not null and status=1".
												" and time_accepted between ? and ?".
												" group by facility_id";

		Log::info($queryReferredOutSpecimen);
		$referredSpecimens = DB::select(DB::raw($queryReferredOutSpecimen), array($from, $toPlusOne));
		Log::info("MoH 706: End fetch referred out specimen");

		$table = '<!-- URINALYSIS -->
			<div class="col-sm-12">
				<strong>URINE ANALYSIS</strong>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Urine Chemistry</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>&lt;5yrs</th>
							<th>5-14yrs</th>
							<th>&gt;14yrs</th>
						</tr>
					</thead>';

				$urinaId = TestType::getTestTypeIdByTestName('Urinalysis');
				$urinalysis = TestType::find($urinaId);
				$urineChem = TestType::getTestTypeIdByTestName('Urine Chemistry');
				$urineChemistry = TestType::find($urineChem);
				$measures = TestTypeMeasure::where('test_type_id', $urinaId)->orderBy('measure_id', 'DESC')->get();
				$table.='<tbody>
						<tr>
							<td>Totals</td>';
						foreach ($sex as $gender) {
							$table.='<td>'.($this->getGroupedTestCounts($urinalysis, [$gender], null, $from, $toPlusOne)+$this->getGroupedTestCounts($urineChemistry, [$gender], null, $from, $toPlusOne)).'</td>';
						}
						$table.='<td>'.($this->getGroupedTestCounts($urinalysis, null, null, $from, $toPlusOne)+$this->getGroupedTestCounts($urineChemistry, null, null, $from, $toPlusOne)).'</td>';
						foreach ($ageRanges as $ageRange) {
							$table.='<td>'.($this->getGroupedTestCounts($urinalysis, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne)+$this->getGroupedTestCounts($urineChemistry, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne)).'</td>';
						}	
					$table.='</tr>';
				
				foreach ($measures as $measure) {
					$tMeasure = Measure::find($measure->measure_id);
					if(in_array($tMeasure->name, ['ph', 'Epithelial cells', 'Pus cells', 'S. haematobium', 'T. vaginalis', 'Yeast cells', 'Red blood cells', 'Bacteria', 'Spermatozoa'])){continue;}
					$table.='<tr>
								<td>'.$tMeasure->name.'</td>';
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ageRanges as $ageRange) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, $ageRange, $from, $toPlusOne, null, 1).'</td>';
							}
							$table.='</tr>';
				}
				Log::info("MoH 706: End render urine chemistry");

				$table.='<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Urine Microscopy</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>&lt;5yrs</th>
							<th>5-14yrs</th>
							<th>&gt;14yrs</th>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td>Totals</td>';
				$urineMic = TestType::getTestTypeIdByTestName('Urine Microscopy');
				$urineMicroscopy = TestType::find($urineMic);
				$measures = TestTypeMeasure::where('test_type_id', $urinaId)->orderBy('measure_id', 'DESC')->get();
						foreach ($sex as $gender) {
							$table.='<td>'.($this->getGroupedTestCounts($urinalysis, [$gender], null, $from, $toPlusOne)+$this->getGroupedTestCounts($urineMicroscopy, [$gender], null, $from, $toPlusOne)).'</td>';
						}
						$table.='<td>'.($this->getGroupedTestCounts($urinalysis, null, null, $from, $toPlusOne)+$this->getGroupedTestCounts($urineMicroscopy, null, null, $from, $toPlusOne)).'</td>';
						foreach ($ageRanges as $ageRange) {
							$table.='<td>'.($this->getGroupedTestCounts($urinalysis, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne)+$this->getGroupedTestCounts($urineMicroscopy, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne)).'</td>';
						}	
					$table.='</tr>';
				
				foreach ($measures as $measure) {
					$tMeasure = Measure::find($measure->measure_id);
					if(in_array($tMeasure->name, ['Leucocytes', 'Nitrites', 'Glucose', 'pH', 'Bilirubin', 'Ketones', 'Proteins', 'Blood', 'Urobilinogen Phenlpyruvic acid'])){continue;}
					$table.='<tr>
								<td>'.$tMeasure->name.'</td>';
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ageRanges as $ageRange) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, $ageRange, $from, $toPlusOne, null, 1).'</td>';
							}
							$table.='</tr>';
				}
				Log::info("MoH 706: End render urine Microscopy");

				$table.='<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Blood Chemistry</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>';
				$bloodChem = TestType::getTestTypeIdByTestName('Blood Sugar fasting');
				$bloodChemistry = TestType::find($bloodChem);
				$measures = TestTypeMeasure::where('test_type_id', $bloodChem)->orderBy('measure_id', 'DESC')->get();
					$table.='<tr>
							<td>Totals</td>';
					foreach ($sex as $gender) {
						$table.='<td>'.$this->getGroupedTestCounts($bloodChemistry, [$gender], null, $from, $toPlusOne).'</td>';
					}
					$table.='<td>'.$this->getGroupedTestCounts($bloodChemistry, null, null, $from, $toPlusOne).'</td>';
					foreach ($ageRanges as $ageRange) {
						$table.='<td>'.$this->getGroupedTestCounts($bloodChemistry, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne).'</td>';
					}
					foreach ($measures as $measure) {
						$tMeasure = Measure::find($measure->measure_id);	
						$table.='<tr>
								<td>'.$tMeasure->name.'</td>';
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, ['Low', 'Normal', 'High'], null).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
							$table.='</tr>';
					}
				Log::info("MoH 706: End render blood chemistry");

					$table.='<tr>
							<td>OGTT</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Renal function tests</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>';
				$rfts = TestType::getTestTypeIdByTestName('RFTS');
				$rft = TestType::find($rfts);
				$measures = TestTypeMeasure::where('test_type_id', $rfts)->orderBy('measure_id', 'DESC')->get();
				$table.='<tr>
						<td>Totals</td>';
	        		foreach ($sex as $gender) {
						$table.='<td>'.$this->getGroupedTestCounts($rft, [$gender], null, $from, $toPlusOne).'</td>';
					}
					$table.='<td>'.$this->getGroupedTestCounts($rft, null, null, $from, $toPlusOne).'</td>';
					foreach ($ageRanges as $ageRange) {
						$table.='<td>'.$this->getGroupedTestCounts($rft, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne).'</td>';
					}	
				$table.='</tr>';
				foreach ($measures as $measure) {
					$name = Measure::find($measure->measure_id)->name;
					if($name == 'Electrolytes'){
						continue;
					}
					$tMeasure = Measure::find($measure->measure_id);
					$table.='<tr>
								<td>'.$tMeasure->name.'</td>';
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
							$table.='</tr>';
				}
				Log::info("MoH 706: End render renal function tests");

				$table.='</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Liver Function Tests</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>';
				$lfts = TestType::getTestTypeIdByTestName('LFTS');
				$lft = TestType::find($lfts);
				$measures = TestTypeMeasure::where('test_type_id', $lfts)->orderBy('measure_id', 'DESC')->get();
				$table.='<tr>
						<td>Totals</td>';
		        		foreach ($sex as $gender) {
							$table.='<td>'.$this->getGroupedTestCounts($lft, [$gender], null, $from, $toPlusOne).'</td>';
						}
						$table.='<td>'.$this->getGroupedTestCounts($lft, null, null, $from, $toPlusOne).'</td>';
						foreach ($ageRanges as $ageRange) {
							$table.='<td>'.$this->getGroupedTestCounts($lft, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne).'</td>';
						}	
					$table.='</tr>';
				foreach ($measures as $measure) {
					$name = Measure::find($measure->measure_id)->name;
					if($name == 'SGOT'){
						$name = 'ASAT (SGOT)';
					}
					if($name == 'ALAT'){
						$name = 'ASAT (SGPT)';
					}
					if($name == 'Total Proteins'){
						$name = 'Serum Protein';
					}
					$tMeasure = Measure::find($measure->measure_id);
					$table.='<tr>
								<td>'.$tMeasure->name.'</td>';
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
							$table.='</tr>';
				}

				Log::info("MoH 706: End render Liver function tests");

				$table.='<tr>
							<td>Gamma GT</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Lipid Profile</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Totals</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr><tr>
							<td>Amylase</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('Serum Amylase'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, $ageRange, $from, $toPlusOne, [$range], 1).'</td>';
							}
						$table.='</tr><tr>
							<td>Total cholestrol</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('cholestrol'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
						$table.='</tr><tr>
							<td>Tryglycerides</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('Tryglycerides'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
						$table.='</tr><tr>
							<td>HDL</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('HDL'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
						$table.='</tr><tr>
							<td>LDL</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('LDL'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}
						$table.='</tr>
						<tr>
							<td>PSA</td>';
							$tMeasure = Measure::find(Measure::getMeasureIdByName('PSA'));
							foreach ($sex as $gender) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
							}
							$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
							foreach ($ranges as $range) {
								$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range], 1).'</td>';
							}

				Log::info("MoH 706: End render lipid Profile tests");

						$table.='</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">CSF Chemistry</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>';
				$csf = TestType::getTestTypeIdByTestName('CSF for biochemistry');
				$bioCsf = TestType::find($csf);
				$table.='<tr>
					<td>Totals</td>';
	        		foreach ($sex as $gender) {
						$table.='<td>'.$this->getGroupedTestCounts($bioCsf, [$gender], null, $from, $toPlusOne).'</td>';
					}
					$table.='<td>'.$this->getGroupedTestCounts($bioCsf, null, null, $from, $toPlusOne).'</td>';
					foreach ($ageRanges as $ageRange) {
						$table.='<td>'.$this->getGroupedTestCounts($bioCsf, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne).'</td>';
					}	
				$table.='</tr>';
				$measures = TestTypeMeasure::where('test_type_id', $csf)->orderBy('measure_id', 'DESC')->get();
				foreach ($measures as $measure) {
					$name = Measure::find($measure->measure_id)->name;
					$table.='<tr>
							<td>'.$name.'</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>';
				}

				Log::info("MoH 706: End render CSF Chemistry tests");

				$table.='</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Body Fluids</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Totals</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
						</tr>
						<tr>
							<td>Proteins</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Glucose</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Acid phosphatase</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Bence jones protein</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Thyroid Function Tests</th>
							<th colspan="2">No. Exam</th>
							<th colspan="4"> Number positive</th>
						</tr>
						<tr>
							<th>M</th>
							<th>F</th>
							<th>Total</th>
							<th>Low</th>
							<th>Normal</th>
							<th>High</th>
						</tr>
					</thead>
					<tbody>';
				$tfts = TestType::getTestTypeIdByTestName('TFT');
				$tft = TestType::find($tfts);
				$table.='<tr>
					<td>Totals</td>';
	        		foreach ($sex as $gender) {
						$table.='<td>'.$this->getGroupedTestCounts($tft, [$gender], null, $from, $toPlusOne).'</td>';
					}
					$table.='<td>'.$this->getGroupedTestCounts($tft, null, null, $from, $toPlusOne).'</td>';
					foreach ($ageRanges as $ageRange) {
						$table.='<td>'.$this->getGroupedTestCounts($tft, [Patient::MALE, Patient::FEMALE], $ageRange, $from, $toPlusOne).'</td>';
					}	
				$table.='</tr>';
				$measures = TestTypeMeasure::where('test_type_id', $tfts)->orderBy('measure_id', 'ASC')->get();
				foreach ($measures as $measure) {
					$tMeasure = Measure::find($measure->measure_id);
					$table.='<tr>
						<td>'.$tMeasure->name.'</td>';
					foreach ($sex as $gender) {
						$table.='<td>'.$this->getTotalTestResults($tMeasure, [$gender], null, $from, $toPlusOne, null, null).'</td>';
					}
					$table.='<td>'.$this->getTotalTestResults($tMeasure, $sex, null, $from, $toPlusOne, null, 1).'</td>';
					foreach ($ranges as $range) {
						$table.='<td>'.$this->getTotalTestResults($tMeasure, null, null, $from, $toPlusOne, [$range]).'</td>';
					}
					$table.='</tr>';
				}

				Log::info("MoH 706: End render Thyroid function tests");

				$table.='<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
			<!-- URINALYSIS -->
			<!-- PARASITOLOGY -->
			<div class="col-sm-12">
				<strong>PARASITOLOGY</strong>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th colspan="5">Blood Smears</th>
						</tr>
						<tr>
							<th rowspan="2">Malaria</th>
							<th colspan="4">Positive</th>
						</tr>
						<tr>
							<th>Total Done</th>
							<th>&lt;5yrs</th>
							<th>5-14yrs</th>
							<th>&gt;14yrs</th>
						</tr>
					</thead>';
				$bs = TestType::getTestTypeIdByTestName('Bs for mps');
				$bs4mps = TestType::find($bs);
				$table.='<tbody>
						<tr>
							<td></td>
							<td>'.$this->getGroupedTestCounts($bs4mps, null, null, $from, $toPlusOne).'</td>';
						foreach ($ageRanges as $ageRange) {
							$table.='<td>'.$this->getGroupedTestCounts($bs4mps, null, $ageRange, $from, $toPlusOne).'</td>';
						}
					$table.='</tr>
						<tr style="text-align:right;">
							<td>Falciparum</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr style="text-align:right;">
							<td>Ovale</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr style="text-align:right;">
							<td>Malariae</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr style="text-align:right;">
							<td>Vivax</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td><strong>Borrelia</strong></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td><strong>Microfilariae</strong></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td><strong>Trypanosomes</strong></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="5"><strong>Genital Smears</strong></td>
						</tr>
						<tr>
							<td>Total</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>T. vaginalis</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>S. haematobium</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Yeast cells</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Others</td>
							<td style="background-color: #CCCCCC;"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="5"><strong>Spleen/bone marrow</strong></td>
						</tr>
						<tr>
							<td>Total</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>L. donovani</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>';

				Log::info("MoH 706: End render Malaria tests");

				$stool = TestType::getTestTypeIdByTestName('Stool for O/C');
				$stoolForOc = TestType::find($stool);
				$measures = TestTypeMeasure::where('test_type_id', $stool)->orderBy('measure_id', 'DESC')->get();
				$table.='<td colspan="5"><strong>Stool</strong></td>
						</tr>
						<tr>
							<td>Total</td>
							<td>'.$this->getGroupedTestCounts($stoolForOc, null, null, $from, $toPlusOne).'</td>';
							foreach ($ageRanges as $ageRange) {
								$table.='<td>'.$this->getGroupedTestCounts($stoolForOc, null, $ageRange, $from, $toPlusOne).'</td>';
							}
						$table.='</tr>';
						foreach ($measures as $measure) {
							$tMeasure = Measure::find($measure->measure_id);
							foreach ($tMeasure->measureRanges as $range) {
								if($range->alphanumeric=='O#C not seen'){ continue; }
							$table.='<tr>
									<td>'.$range->alphanumeric.'</td>';
								$table.='<td style="background-color: #CCCCCC;"></td>';
								foreach ($ageRanges as $ageRange) {
									$table.='<td>'.$this->getTotalTestResults($tMeasure, null, $ageRange, $from, $toPlusOne, [$range->alphanumeric]).'</td>';
								}
								$table.='</tr>';
							}
						}

				Log::info("MoH 706: End render Stool for O/C tests");

						$table.='<tr>
							<td colspan="5"><strong>Lavages</strong></td>
						</tr>
						<tr>
							<td>Total</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
			<!-- PARASITOLOGY -->
			<!-- BACTERIOLOGY -->
			<div class="col-sm-12">
				<strong>BACTERIOLOGY</strong>
				<div class="row">
					<div class="col-sm-4">
						<table class="table table-condensed report-table-border" style="padding-right:5px;">
							<tbody style="text-align:right;">
								<tr>
									<td>Total examinations done</td>
									<td></td>
								</tr>';
						foreach ($specTypeIds as $key) {
							if(in_array(SpecimenType::find($key->spec_id)->name, ['Aspirate', 'Pleural Tap', 'Synovial Fluid', 'Sputum', 'Ascitic Tap', 'Semen', 'Skin'])){
								continue;
							}
							$totalCount = DB::select(DB::raw("select count(specimen_id) as per_spec_count from tests".
															 " join specimens on tests.specimen_id=specimens.id".
															 " join test_types on tests.test_type_id=test_types.id".
															 " where specimens.specimen_type_id=?".
															 " and test_types.test_category_id=?".
															 " and test_status_id in(?,?)".
															 " and tests.time_created BETWEEN ? and ?;"), 
															[$key->spec_id, $labSecId, Test::COMPLETED, Test::VERIFIED, $from, $toPlusOne]);
							$table.='<tr>
									<td>'.SpecimenType::find($key->spec_id)->name.'</td>
									<td>'.$totalCount[0]->per_spec_count.'</td>
								</tr>';
						}

				Log::info("MoH 706: End render Aspirate, Pleural Tap, Synovial Fluid, Sputum, Ascitic Tap tests");

						$table.='</tr>
									<td>Rectal swab</td>
									<td>0</td>
								</tr>
								</tr>
									<td>Water</td>
									<td>0</td>
								</tr>
								</tr>
									<td>Food</td>
									<td>0</td>
								</tr>
								</tr>
									<td>Other (specify)....</td>
									<td></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-sm-8">
						<table class="table table-condensed report-table-border">
							<tbody>
								<tr>
									<td colspan="3">Drugs</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="3">Sensitivity (Total done)</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="3">Resistance per drug</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td rowspan="3">KOH Preparations</td>
									<td>Fungi</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td colspan="2">Others (specify)</td>
								</tr>
								<tr>
									<td>Others</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td>...</td>
									<td></td>
								</tr>
								<tr>
									<td>Total</td>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
									<td>...</td>
									<td></td>
								</tr>
							</tbody>
						</table>
						<p>SPUTUM</p>
						<table class="table table-condensed report-table-border">
							<tbody>
								<tr>
									<td></td>
									<td>Total</td>
									<td>Positive</td>
								</tr>
								<tr>
									<td>TB new suspects</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td>Followup</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td>TB smears</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td>MDR</td>
									<td></td>
									<td></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<table class="table table-condensed report-table-border">
					<tbody>
						<tr><td></td>';
					foreach ($specimen_types as $spec) {
						$table.='<td>'.$spec.'</td>';
					}	
					$table.='</tr>';
					foreach ($isolates as $isolate) {
						$table.='<tr>
							<td>'.$isolate.'</td>';
							foreach ($specimen_types as $spec) {
								$table.='<td>'.TestResult::microCounts($isolate,$spec, $from, $toPlusOne)[0]->total.'</td>';
							}
						$table.='</tr>';
					}

				Log::info("MoH 706: End render Microbiology isolate counts tests");

					$table.='<tr>
							<td colspan="11">Specify species of each isolate</td>
						</tr>
					</tbody>
				</table>
				<div class="row">
					<div class="col-sm-12">
						<strong>HEMATOLOGY REPORT</strong>
						<table class="table table-condensed report-table-border">
							<thead>
								<tr>
									<th colspan="2">Type of examination</th>
									<th>No. of Tests</th>
									<th>Controls</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="2">Full blood count</td>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Full haemogram')), null, null, $from, $toPlusOne).'</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Manual WBC counts</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Peripheral blood films</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Erythrocyte Sedimentation rate</td>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('ESR')), null, null, $from, $toPlusOne).'</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Sickling test</td>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Sickling test')), null, null, $from, $toPlusOne).'</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">HB electrophoresis</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">G6PD screening</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Bleeding time</td>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Bleeding time test')), null, null, $from, $toPlusOne).'</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Clotting time</td>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Clotting time test')), null, null, $from, $toPlusOne).'</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Prothrombin test</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Partial prothrombin time</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td colspan="2">Bone Marrow Aspirates</td>
									<td></td>
									<td style="background-color: #CCCCCC;"></td>
								</tr>
								<tr>
									<td colspan="2">Reticulocyte counts</td>
									<td></td>
									<td style="background-color: #CCCCCC;"></td>
								</tr>
								<tr>
									<td colspan="2">Others</td>
									<td></td>
									<td style="background-color: #CCCCCC;"></td>
								</tr>
								<tr>
									<td rowspan="2">Haemoglobin</td>
									<td>No. Tests</td>
									<td>&lt;5</td>
									<td>5&lt;Hb&lt;10</td>
								</tr>
								<tr>
									<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('HB')), null, null, $from, $toPlusOne).'</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td rowspan="2">CD4/CD8</td>
									<td>No. Tests</td>
									<td>&lt;200</td>
									<td>200-350</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td rowspan="2">CD4%</td>
									<td>No. Tests</td>
									<td>&lt;25%</td>
									<td>&gt;25%</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td rowspan="2">Peripheral Blood Films</td>
									<td>Parasites</td>
									<td colspan="2">No. smears with inclusions</td>
								</tr>
								<tr>
									<td></td>
									<td></td>
									<td colspan="2"></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-sm-12">
						<strong>BLOOD GROUPING AND CROSSMATCH REPORT</strong>
						<div class="row">
							<div class="col-sm-6">
								<table class="table table-condensed report-table-border">
									<tbody>
										<tr>
											<td>Total groupings done</td>
											<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('GXM')), null, null, $from, $toPlusOne).'</td>
										</tr>
										<tr>
											<td>Blood units grouped</td>
											<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Blood Grouping')), null, null, $from, $toPlusOne).'</td>
										</tr>
										<tr>
											<td>Total transfusion reactions</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood cross matches</td>
											<td>'.$this->getGroupedTestCounts(TestType::find(TestType::getTestTypeIdByTestName('Cross Match')), null, null, $from, $toPlusOne).'</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="col-sm-6">
								<strong>Blood safety</strong>
								<table class="table table-condensed report-table-border">
									<tbody>
										<tr>
											<td>Measure</td>
											<td>Number</td>
										</tr>
										<tr>
											<td>A. Blood units collected from regional blood transfusion centres</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood units collected from other centres and screened at health facility</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood units screened at health facility that are HIV positive</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood units screened at health facility that are Hepatitis positive</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood units positive for other infections</td>
											<td></td>
										</tr>
										<tr>
											<td>Blood units transfered</td>
											<td></td>
										</tr>
										<tr>
											<td rowspan="2">General remarks .............................</td>
											<td rowspan="2"></td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- BACTERIOLOGY -->
			<!-- HISTOLOGY AND CYTOLOGY -->
			<div class="col-sm-12">
				<strong>HISTOLOGY AND CYTOLOGY REPORT</strong>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2"></th>
							<th rowspan="2">Total</th>
							<th rowspan="2">Normal</th>
							<th rowspan="2">Infective</th>
							<th colspan="2">Non-infective</th>
							<th colspan="3">Positive findings</th>
						</tr>
						<tr>
							<th>Benign</th>
							<th>Malignant</th>
							<th>&lt;5 yrs</th>
							<th>5-14 yrs</th>
							<th>&gt;14 yrs</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="9">SMEARS</td>
						</tr>
						<tr>
							<td>Pap Smear</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Tissue Impressions</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td colspan="9">TISSUE ASPIRATES (FNA)</td>
						</tr>
						<tr>
							<td></td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td colspan="9">FLUID CYTOLOGY</td>
						</tr>
						<tr>
							<td>Ascitic fluid</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>CSF</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Pleural fluid</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="9">TISSUE HISTOLOGY</td>
						</tr>
						<tr>
							<td>Cervix</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Prostrate</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Breast</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Ovarian cyst</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Fibroids</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Lymph nodes</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<strong>SEROLOGY REPORT</strong>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th rowspan="2">Serological test</th>
							<th colspan="2">Total</th>
							<th colspan="2">&lt;5 yrs</th>
							<th colspan="2">5-14 yrs</th>
							<th colspan="2">&gt;14 yrs</th>
						</tr>
						<tr>
							<th>Tested</th>
							<th>No. +ve</th>
							<th>Tested</th>
							<th>No. +ve</th>
							<th>Tested</th>
							<th>No. +ve</th>
							<th>Tested</th>
							<th>No. +ve</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Rapid Plasma Region</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('VDRL')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('VDRL')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('VDRL'), $ageRange))==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('VDRL'), $ageRange) as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}
							$table.='</tr>
						<tr>
							<td>TPHA</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>ASO Test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Asot')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Asot')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Asot'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}
							$table.='</tr>
						<tr>
							<td>HIV Test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Rapid HIV test')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Rapid HIV test')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Rapid HIV test'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}
							$table.='</tr>
						<tr>
							<td>Widal Test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Widal')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Widal')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Widal'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render haematology initial tests");

							$table.='</tr>
						<tr>
							<td>Brucella Test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Brucella')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Brucella')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Brucella'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render Brucella tests");

							$table.='</tr>
						<tr>
							<td>Rheumatoid Factor Tests</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('RF')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('RF')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('RF'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render Rheumatoid Factor tests");

							$table.='</tr>
						<tr>
							<td>Cryptococcal Antigen</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Helicobacter pylori test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('H pylori')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('H pylori')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('H pylori'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render H pylori tests");

							$table.='</tr>
						<tr>
							<td>Hepatitis A test</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>
							<td>0</td>';
							$table.='</tr>
						<tr>
							<td>Hepatitis B test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis B')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis B')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis B'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render Hepatitis B tests");

							$table.='</tr>
						<tr>
							<td>Hepatitis C test</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis C')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis C')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Hepatitis C'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td>0</td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td>'.$count->positive.'</td>';
									}
								}
							}

				Log::info("MoH 706: End render Hepatitis C tests");

							$table.='</tr>
						<tr>
							<td>Viral Load</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Viral load')))==0)
							{
								$table.='<td>0</td>
									<td style="background-color: #CCCCCC;"></td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Viral load')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td style="background-color: #CCCCCC;"></td>';
								}
							}
							foreach ($ageRanges as $ageRange) {
								$data = TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('Viral load'), $ageRange);
								if(count($data)==0)
								{
									$table.='<td>0</td>
									<td style="background-color: #CCCCCC;"></td>';
								}
								else{
									foreach($data as $count){
										$table.='<td>'.$count->total.'</td>
										<td style="background-color: #CCCCCC;"></td>';
									}
								}
							}

				Log::info("MoH 706: End render Viral Load tests");

							$table.='</tr>
						<tr>
							<td>Formal Gel Test</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
							<td>N/S</td>
						</tr>
						<tr>
							<td>Other Tests</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<br />
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th>Dried Blood Spots</th>
							<th>Tested</th>
							<th># +ve</th>
							<th>Discrepant</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Early Infant Diagnosis of HIV</td>';
							if(count(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('eid of hiv')))==0)
							{
								$table.='<td>0</td>
									<td>0</td>';
							}
							else{
								foreach(TestType::getPrevalenceCounts($from, $to, TestType::getTestTypeIdByTestName('eid of hiv')) as $count){
									if(count($count)==0)
										{
											$count->total=0;
											$count->positive=0;
										}
									$table.='<td>'.$count->total.'</td>
									<td>'.$count->positive.'</td>';
								}
							}

				Log::info("MoH 706: End render EID tests");

							$table.='<td></td>
						</tr>
						<tr>
							<td>Quality Assurance</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Discordant couples</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Others</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<p><strong>Specimen referral to higher levels</strong></p>
				<table class="table table-condensed report-table-border">
					<thead>
						<tr>
							<th>Specimen</th>
							<th>No</th>
							<th>Sent to</th>
							<th>No. of Reports/results received</th>
						</tr>
					</thead>
					<tbody>';
				if($referredSpecimens){
					foreach ($referredSpecimens as $referredSpecimen) {
						$table.='<tr>
								<td>'.$referredSpecimen->spec.'</td>
								<td>'.$referredSpecimen->tot.'</td>
								<td>'.$referredSpecimen->facility.'</td>
								<td></td>
							</tr>';
					}
				}else{
					$table.='<tr>
								<td colspan="4">'.trans('messages.no-records-found').'</td>
							</tr>';
				}
				$table.='</tbody>
				</table>
			</div>
			<!-- HISTOLOGY AND CYTOLOGY -->';

				Log::info("MoH 706: End render referred specimen data");

		if(Input::has('excel')){
			$date = date("Ymdhi");
			$fileName = "MOH706_".$date.".xls";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = $table;
	    	return Response::make($content,200, $headers);
		}
		else{
			Log::info("End MoH 706");
			return View::make('reports.moh.index')->with('table', $table)->with('from', $from)->with('end', $end);
		}
	}

	public function moh706v201410(){

		$startDate = Input::get('start')?Input::get('start'):date('Y-m-01');
		$endDate = Input::get('end')?Input::get('end'):date('Y-m-d');

		$mohData['1_1_urine_chemistry_total'] = Test::getCount(array("'Urinalysis'", "'Urine chemistry'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['1_2_glucose'] = Test::getCountByResult("Urinalysis", "Glucose", "high", $startDate, $endDate) + Test::getCountByResult("Urine chemistry", "Glucose", "HIGH", $startDate, $endDate);
		$mohData['1_3_ketones'] = Test::getCountByResult("Urinalysis", "Ketones", "Positive", $startDate, $endDate) + Test::getCountByResult("Urine chemistry", "Ketones", "Positive", $startDate, $endDate);
		$mohData['1_4_proteins'] = Test::getCountByResult("Urinalysis", "Protein", "HIGH", $startDate, $endDate) + Test::getCountByResult("Urine chemistry", "Protein", "HIGH", $startDate, $endDate);
		$mohData['1_5_urine_microscopy_total'] = Test::getCount(array("'Urinalysis'", "'Urine microscopy'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['1_6_puss_cells'] = "N/S";
		$mohData['1_7_s_haematobium'] = "N/S";
		$mohData['1_8_t_vaginalis'] = "N/S";
		$mohData['1_9_yeast_cells'] = "N/S";
		$mohData['1_10_bacteria'] = "N/S";

		$mohData['2_1_fasting_blood_sugar_total'] = Test::getCount(array("'Blood sugar fasting'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), Test::TIME_COMPLETED);
		$mohData['2_1_fasting_blood_sugar_low'] = Test::getCountByResult("Blood sugar fasting", "fasting", "LOW", $startDate, $endDate);
		$mohData['2_1_fasting_blood_sugar_high'] = Test::getCountByResult("Blood sugar fasting", "fasting", "HIGH", $startDate, $endDate);

		$mohData['2_1_random_blood_sugar_total'] = Test::getCount(array("'blood sugar random'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), Test::TIME_COMPLETED);
		$mohData['2_1_random_blood_sugar_low'] = Test::getCountByResult("blood sugar random", "blood sugar random", "LOW", $startDate, $endDate);
		$mohData['2_1_random_blood_sugar_high'] = Test::getCountByResult("blood sugar random", "blood sugar random", "HIGH", $startDate, $endDate);
		$mohData['2_2_ogtt_total'] = "N/S";
		$mohData['2_2_ogtt_low'] = "N/S";
		$mohData['2_2_ogtt_high'] = "N/S";

		$mohData['2_3_renal_function_total'] = Test::getCount(array("'RFTS'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));

		$mohData['2_4_creatinine_low'] = Test::getCountByResult("RFTS", "Creatinine", "LOW", $startDate, $endDate);
		$mohData['2_4_creatinine_high'] = Test::getCountByResult("RFTS", "Creatinine", "HIGH", $startDate, $endDate);
		$mohData['2_5_urea_low'] = Test::getCountByResult("RFTS", "Urea", "LOW", $startDate, $endDate);
		$mohData['2_5_urea_high'] = Test::getCountByResult("RFTS", "Urea", "HIGH", $startDate, $endDate);
		$mohData['2_5_sodium_low'] = Test::getCountByResult("RFTS", "sodium", "LOW", $startDate, $endDate);
		$mohData['2_5_sodium_high'] = Test::getCountByResult("RFTS", "sodium", "HIGH", $startDate, $endDate);
		$mohData['2_6_potassium_low'] = Test::getCountByResult("RFTS", "potassium", "LOW", $startDate, $endDate);
		$mohData['2_6_potassium_high'] = Test::getCountByResult("RFTS", "potassium", "HIGH", $startDate, $endDate);
		$mohData['2_7_chlorides_low'] = Test::getCountByResult("RFTS", "chloride", "LOW", $startDate, $endDate);
		$mohData['2_7_chlorides_high'] = Test::getCountByResult("RFTS", "chloride", "HIGH", $startDate, $endDate);

		$mohData['2_8_liver_function_total'] = Test::getCount(array("'LFTS'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['2_9_direct_bilirubin_low'] = Test::getCountByResult("LFTS", "Direct Bilirubin", "LOW", $startDate, $endDate);
		$mohData['2_9_direct_bilirubin_high'] = Test::getCountByResult("LFTS", "Direct Bilirubin", "HIGH", $startDate, $endDate);
		$mohData['2_10_total_bilirubin_low'] = Test::getCountByResult("LFTS", "Total Bilirubin", "LOW", $startDate, $endDate);
		$mohData['2_10_total_bilirubin_high'] = Test::getCountByResult("LFTS", "Total Bilirubin", "HIGH", $startDate, $endDate);
		$mohData['2_11_asat_low'] = Test::getCountByResult("LFTS", "AST/GOT", "LOW", $startDate, $endDate);
		$mohData['2_11_asat_high'] = Test::getCountByResult("LFTS", "AST/GOT", "HIGH", $startDate, $endDate);
		$mohData['2_12_alat_low'] = Test::getCountByResult("LFTS", "ALAT/GPT", "LOW", $startDate, $endDate);
		$mohData['2_12_alat_high'] = Test::getCountByResult("LFTS", "ALAT/GPT", "HIGH", $startDate, $endDate);
		$mohData['2_13_serum_protein_low'] = Test::getCountByResult("LFTS", "Total Proteins", "LOW", $startDate, $endDate);
		$mohData['2_13_serum_protein_high'] = Test::getCountByResult("LFTS", "Total Proteins", "HIGH", $startDate, $endDate);
		$mohData['2_14_albumin_low'] = Test::getCountByResult("LFTS", "Albumin", "LOW", $startDate, $endDate);
		$mohData['2_14_albumin_high'] = Test::getCountByResult("LFTS", "Albumin", "HIGH", $startDate, $endDate);
		$mohData['2_alkaline_phosphatase_low'] = Test::getCountByResult("LFTS", "Alkaline Phosphate", "LOW", $startDate, $endDate);
		$mohData['2_alkaline_phosphatase_high'] = Test::getCountByResult("LFTS", "Alkaline Phosphate", "HIGH", $startDate, $endDate);

		$mohData['2_16_lipid_profile_total'] = Test::getCount(array("'LIPID PROFILE'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
                $mohData['2_17_cholesterol_low'] = Test::getCountByResult("LIPID PROFILE", "CHOLESTROL", "LOW", $startDate, $endDate);
                $mohData['2_17_cholesterol_high'] = Test::getCountByResult("LIPID PROFILE", "CHOLESTROL", "HIGH", $startDate, $endDate);
                $mohData['2_18_triglycerides_low'] = Test::getCountByResult("LIPID PROFILE", "Triglycerides", "LOW", $startDate, $endDate);
                $mohData['2_18_triglycerides_high'] = Test::getCountByResult("LIPID PROFILE", "Triglycerides", "HIGH", $startDate, $endDate);
                $mohData['2_19_ldl_low'] = Test::getCountByResult("LIPID PROFILE", "LDL cholestrol", "LOW", $startDate, $endDate);
                $mohData['2_19_ldl_high'] = Test::getCountByResult("LIPID PROFILE", "LDL cholestrol", "HIGH", $startDate, $endDate);

		$mohData['2_20_t3_total'] = "N/S";
		$mohData['2_20_t3_low'] = "N/S";
		$mohData['2_20_t3_high'] = "N/S";

		$mohData['2_21_t4_total'] = "N/S";
		$mohData['2_21_t4_low'] = "N/S";
		$mohData['2_21_t4_high'] = "N/S";

		$mohData['2_22_tsh_total'] = "N/S";
		$mohData['2_22_tsh_low'] = "N/S";
		$mohData['2_22_tsh_high'] = "N/S";

		$mohData['2_23_psa_total'] = Test::getCount(array("'PSA'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['2_23_psa_low'] = "N/S";
		$mohData['2_23_psa_high'] = "N/S";

		$mohData['2_24_cea_total'] = "N/S";
		$mohData['2_24_cea_low'] = "N/S";
		$mohData['2_24_cea_high'] = "N/S";

		$mohData['2_25_c15_total'] = "N/S";
		$mohData['2_25_c15_low'] = "N/S";
		$mohData['2_25_c15_high'] = "N/S";

		$mohData['2_26_proteins_total'] = "N/S";
		$mohData['2_26_proteins_low'] = "N/S";
		$mohData['2_26_proteins_high'] = "N/S";

		$mohData['2_27_glucose_total'] = "N/S";
		$mohData['2_27_glucose_low'] = "N/S";
		$mohData['2_27_glucose_high'] = "N/S";

		$mohData['3_1_malaria_bs_under_5_total'] = Test::getCountByAge(array("'BS for mps'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), array(0, 5));
		$mohData['3_1_malaria_bs_under_5_positive'] = Test::getCountByResultValue("BS for mps", "BS for mps", " AND tr.result != 'No mps seen'", $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), array(0, 5));
		$mohData['3_2_malaria_bs_over_5_total'] = Test::getCountByAge(array("'BS for mps'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), array(5, 120	));
		$mohData['3_2_malaria_bs_over_5_positive'] = Test::getCountByResultValue("BS for mps", "BS for mps", " AND tr.result != 'No mps seen'", $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED), array(5, 200));
		$mohData['3_3_malaria_rapid_total'] = "N/S";
		$mohData['3_3_malaria_rapid_positive'] = "N/S";
		$mohData['3_4_taenia_spp'] = "N/S";
		$mohData['3_4_stool_for_oc'] = Test::getCount(array("'Stool for O/C'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['3_5_hymenolepis_nana'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%hymenolepis%'", $startDate, $endDate);
		$mohData['3_6_hookworms'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%hookworm%'", $startDate, $endDate);
		$mohData['3_7_roundworms'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%roundworm%'", $startDate, $endDate);
		$mohData['3_8_s_mansoni'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%mansoni%'", $startDate, $endDate);
		$mohData['3_9_trichuris_trichura'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%trichura%'", $startDate, $endDate);
		$mohData['3_10_amoeba'] = Test::getCountByResultValue("Stool for O/C", "Stool for O/C", " AND tr.result LIKE '%amoeba%'", $startDate, $endDate);

		$mohData['4_1_full_blood_count_total'] = Test::getCount(array("'Full Haemogram'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_1_full_blood_count_low'] = Test::getCountByResultValue("Full Haemogram", "HB", " AND tr.result < 5", $startDate, $endDate);
		$mohData['4_1_full_blood_count_high'] = Test::getCountByResultValue("Full Haemogram", "HB", " AND tr.result >= 5 AND tr.result < 10", $startDate, $endDate);

		$mohData['4_2_hb_other_estimations_total'] = Test::getCount(array("'HB'", "'HB Electrophoresis'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_2_hb_other_estimations_low'] = Test::getCountByResultValue("HB", "HB", " AND tr.result < 5 ", $startDate, $endDate) + Test::getCountByResultValue("HB Electrophoresis", "HB Electrophoresis", " AND tr.result < 5 ", $startDate, $endDate);
		$mohData['4_2_hb_other_estimations_high'] = Test::getCountByResultValue("HB", "HB", " AND tr.result >= 5 AND tr.result < 10", $startDate, $endDate) + Test::getCountByResultValue("HB Electrophoresis", "HB Electrophoresis", " AND tr.result >= 5 AND tr.result < 10", $startDate, $endDate);

		$mohData['4_3_cd4_count_total'] = Test::getCount(array("'CD4'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_3_cd4_under_500'] = Test::getCountByResultValue("CD4", "CD4", " AND tr.result < 500", $startDate, $endDate);

		$mohData['4_4_sickling_test_total'] = Test::getCount(array("'Sickling test'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_4_sickling_test_positive'] = Test::getCountByResultValue("Sickling test", "Sickling test", " AND tr.result = 'Positive'", $startDate, $endDate);
		$mohData['4_5_peripheral_blood_films_total'] = Test::getCount(array("'PBF'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_6_bma_total'] = "N/S";
		$mohData['4_7_coagulaton_profile_total'] = Test::getCount(array("'Coagulation Profile'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['4_8_reticulocyte_count_total'] = "N/S";
		$mohData['4_9_eruthrocyte_sedimentation_rate_total'] = "N/S";
		$mohData['4_9_eruthrocyte_sedimentation_rate_high'] = "N/S";
		$mohData['4_10_total_blood_group_tests_total'] = "N/S";
		$mohData['4_11_blood_units_grouped_total'] = "N/S";
		$mohData['4_12_blood_received_total'] = "N/S";
		$mohData['4_13_blood_collected_total'] = "N/S";
		$mohData['4_14_blood_transfused_total'] = "N/S";
		$mohData['4_15_transfusion_reactions_reported_investigated_total'] = "N/S";
		$mohData['4_16_blood_cross_matched_total'] = "N/S";
		$mohData['4_17_blood_units_discarded_total'] = "N/S";
		$mohData['4_18_hiv_positive'] = "N/S";
		$mohData['4_19_hepatitis_b_positive'] = "N/S";
		$mohData['4_20_hepatitis_c_positive'] = "N/S";
		$mohData['4_21_syphilis_positive'] = "N/S";

		$mohData['5_1_urine_total'] = Test::getCount(array("'Urine Culture and Sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_1_urine_culture_count'] = "N/S";
		$mohData['5_1_urine_culture_postive'] = "N/S";

		$mohData['5_2_pus_swabs_total'] = Test::getCount(array("'Pus swab for culture and sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_2_pus_swabs_culture_count'] = "N/S";
		$mohData['5_2_pus_swabs_culture_positive'] = "N/S";

		$mohData['5_3_high_vaginal_swabs_total'] = Test::getCount(array("'HVS for culture and sensitivity'", "'HVS for microscopy'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_3_high_vaginal_swabs_culture_count'] = Test::getCount(array("'HVS for culture and sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_3_high_vaginal_swabs_culture_positive'] = "N/S";

		$mohData['5_4_throat_swab_total'] = Test::getCount(array("'Throat swab for culture'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_4_throat_swab_culture_count'] = "N/S";
		$mohData['5_4_throat_swab_culture_positive'] = "N/S";

		$mohData['5_5_rectal_swab_total'] = Test::getCount(array("'rectal swab'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_5_rectal_swab_culture_count'] = "N/S";
		$mohData['5_5_rectal_swab_culture_positive'] = "N/S";

		$mohData['5_6_blood_total'] = Test::getCount(array("'Blood Culture and sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_6_blood_culture_count'] = "N/S";
		$mohData['5_6_blood_culture_positive'] = "N/S";

		$mohData['5_7_water_total'] = Test::getCount(array("'Water Analysis'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_7_water_culture_count'] = "N/S";
		$mohData['5_7_water_culture_positive'] = "N/S";

		$mohData['5_8_food_total'] = "N/S";
		$mohData['5_8_food_culture_count'] = "N/S";
		$mohData['5_8_food_culture_positive'] = "N/S";

		$mohData['5_9_urethral_swabs_total'] = Test::getCount(array("'Urethral swab microsopy'", "'Urethral swab culture & sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_9_urethral_swabs_culture_count'] = Test::getCount(array("'Urethral swab culture & sensitivity'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_9_urethral_swabs_culture_positive'] = "N/S";

		$mohData['5_10_stool_cultures_total'] = Test::getCount(array("'Stool for C/S'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_10_stool_cultures_positive'] = "N/S";

		$mohData['5_11_salmonella_typhi_positive'] = "N/S";
		$mohData['5_12_shigella_dysenteriae_type1_positve'] = "N/S";
		$mohData['5_13_e_coli_o_157_h7_positive'] = "N/S";
		$mohData['5_14_v_cholerae_o_1_positive'] = "N/S";
		$mohData['5_15_v_cholerae_o_139_positive'] = "N/S";

		$mohData['5_16_csf_total'] = Test::getCount(array("'CSF'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['5_16_csf_positive'] = "N/S";
		$mohData['5_16_csf_contaminated_count'] = "N/S";
		$mohData['5_17_neisseria_meningitidis_a_positive'] = "N/S";
		$mohData['5_18_neisseria_meningitidis_b_positive'] = "N/S";
		$mohData['5_19_neisseria_meningitidis_c_positive'] = "N/S";
		$mohData['5_20_neisseria_meningitidis_w_135_positive'] = "N/S";
		$mohData['5_21_neisseria_meningitidis_x_positive'] = "N/S";
		$mohData['5_22_neisseria_meningitidis_y_positive'] = "N/S";
		$mohData['5_23_n_meningitidis_indeterminate_positive'] = "N/S";
		$mohData['5_24_streptococcus_pneumoniae_positive'] = "N/S";
		$mohData['5_25_haemophilus_influenzae_type_b_positive'] = "N/S";
		$mohData['5_26_cryptococcal_meningitis_positive'] = "N/S";
		$mohData['5_27_b_anthracis_positive'] = "N/S";
		$mohData['5_28_y_pestis_positive'] = "N/S";
		$mohData['5_29_total_tb_smears_total'] = "N/S";
		$mohData['5_29_total_tb_smears_positive'] = "N/S";
		$mohData['5_30_tb_new_suspects_total'] = "N/S";
		$mohData['5_30_tb_new_suspects_positive'] = "N/S";
		$mohData['5_31_tb_follow_up_total'] = "N/S";
		$mohData['5_31_tb_follow_up_positive'] = "N/S";
		$mohData['5_32_geneXpert_total'] = "N/S";
		$mohData['5_32_geneXpert_positive'] = "N/S";
		$mohData['5_33_mdr_tb_total'] = "N/S";
		$mohData['5_33_mdr_tb_positive'] = "N/S";

		$mohData['6_1_pap_smear_total'] = Test::getCount(array("'Pap Smears'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_1_pap_smear_malignant'] = "N/S";
		$mohData['6_2_touch_preparations_total'] = "N/S";
		$mohData['6_2_touch_preparations_malignant'] = "N/S";
		$mohData['6_3_tissue_impressions_total'] = Test::getCount(array("'Tissue Impressions'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_3_tissue_impressions_malignant'] = "N/S";
		$mohData['6_4_thyroid_total'] = "N/S";
		$mohData['6_4_thyroid_malignant'] = "N/S";
		$mohData['6_5_lymph_nodes_total'] = "N/S";
		$mohData['6_5_lymph_nodes_malignant'] = "N/S";
		$mohData['6_6_liver_total'] = "N/S";
		$mohData['6_6_liver_malignant'] = "N/S";
		$mohData['6_7_breast_total'] = "N/S";
		$mohData['6_7_breast_malignant'] = "N/S";
		$mohData['6_8_soft_tissue_masses_total'] = "N/S";
		$mohData['6_8_soft_tissue_masses_malignant'] = "N/S";
		$mohData['6_9_ascitic_fluid_total'] = "N/S";
		$mohData['6_9_ascitic_fluid_malignant'] = "N/S";
		$mohData['6_10_csf_total'] = "N/S";
		$mohData['6_10_csf_malignant'] = "N/S";
		$mohData['6_11_pleural_fluid_total'] = "N/S";
		$mohData['6_11_pleural_fluid_malignant'] = "N/S";
		$mohData['6_12_urine_total'] = "N/S";
		$mohData['6_12_urine_malignant'] = "N/S";

		$mohData['6_13_cervix_total'] = Test::getCount(array("'Cervix'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_13_cervix_malignant'] = "N/S";
		$mohData['6_14_prostrate_total'] = Test::getCount(array("'Prostrate'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_14_prostrate_malignant'] = "N/S";
		$mohData['6_15_breast_tissue_total'] = Test::getCount(array("'Breast'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_15_breast_tissue_malignant'] = "N/S";
		$mohData['6_16_ovary_total'] = Test::getCount(array("'Ovarian Cyst'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_16_ovary_malignant'] = "N/S";
		$mohData['6_17_uterus_total'] = "N/S";
		$mohData['6_17_uterus_malignant'] = "N/S";
		$mohData['6_18_skin_total'] = "N/S";
		$mohData['6_18_skin_malignant'] = "N/S";
		$mohData['6_19_head_and_neck_total'] = "N/S";
		$mohData['6_19_head_and_neck_malignant'] = "N/S";
		$mohData['6_20_dental_total'] = "N/S";
		$mohData['6_20_dental_malignant'] = "N/S";
		$mohData['6_21_git_total'] = "N/S";
		$mohData['6_21_git_malignant'] = "N/S";
		$mohData['6_22_lymph_node_tissue_total'] = Test::getCount(array("'Lymph Nodes'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['6_22_lymph_node_tissue_malignant'] = "N/S";
		$mohData['6_23_bone_marrow_aspirate_total'] = "N/S";
		$mohData['6_23_bone_marrow_aspirate_malignant'] = "N/S";
		$mohData['6_24_trephine_biopsy_total'] = "N/S";
		$mohData['6_24_trephine_biopsy_malignant'] = "N/S";

		$mohData['7_1_vdrl_total'] = Test::getCount(array("'VDRL'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_1_vdrl_positive'] = "N/S";
		$mohData['7_2_tpha_total'] = "N/S";
		$mohData['7_2_tpha_positive'] = "N/S";

		$mohData['7_3_asot_total'] = Test::getCount(array("'Asot'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_3_asot_positive'] = "N/S";
		$mohData['7_4_hiv_total'] = Test::getCount(array("'HTC- HIV'", "'Rapid HIV test'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_4_hiv_positive'] = "N/S";

		$mohData['7_5_brucella_total'] = Test::getCount(array("'Brucella'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_5_brucella_positive'] = "N/S";

		$mohData['7_6_rheumatoid_factor_total'] = Test::getCount(array("'RF'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_6_rheumatoid_factor_positive'] = "N/S";
		$mohData['7_7_helicobacter_pylori_total'] = Test::getCount(array("'H pylori'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_7_helicobacter_pylori_positive'] = "N/S";

		$mohData['7_8_hepatitis_a_total'] = "N/S";
		$mohData['7_8_hepatitis_a_positive'] = "N/S";

		$mohData['7_9_hepatitis_b_total'] = Test::getCount(array("'HEPATITIS B'", "'hepatitis B Rapid'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_9_hepatitis_b_positive'] = "N/S";

		$mohData['7_10_hepatitis_c_total'] = Test::getCount(array("'HEPATITIS C'", "'hepatitis C Rapid'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_10_hepatitis_c_positive'] = "N/S";

		$mohData['7_11_hcg_total'] = Test::getCount(array("'HCG'"), $startDate, $endDate, array(Test::COMPLETED, Test::VERIFIED));
		$mohData['7_11_hcg_positive'] = "N/S";
		$mohData['7_12_crag_total'] = "N/S";

		$mohData['8_1_cd4_specimen_referred_count'] = "N/S";
		$mohData['8_1_cd4_referred_results_received_count'] = "N/S";
		$mohData['8_2_viral_load_specimen_referred_count'] = "N/S";
		$mohData['8_2_viral_load_referred_results_received_count'] = "N/S";
		$mohData['8_3_eid_specimen_referred_count'] = "N/S";
		$mohData['8_3_eid_referred_results_received_count'] = "N/S";
		$mohData['8_4_discordant_specimen_referred_count'] = "N/S";
		$mohData['8_4_discordant_referred_results_received_count'] = "N/S";
		$mohData['8_5_tb_culture_specimen_referred_count'] = "N/S";
		$mohData['8_5_tb_culture_referred_results_received_count'] = "N/S";
		$mohData['8_6_virological_specimen_referred_count'] = "N/S";
		$mohData['8_6_virological_referred_results_received_count'] = "N/S";
		$mohData['8_7_clinical_chemistry_specimen_referred_count'] = "N/S";
		$mohData['8_7_clinical_chemistry_referred_results_received_count'] = "N/S";
		$mohData['8_8_histology_cytology_specimen_referred_count'] = "N/S";
		$mohData['8_8_histology_cytology_referred_results_received_count'] = "N/S";
		$mohData['8_9_haematological_specimen_referred_count'] = "N/S";
		$mohData['8_9_haematological_referred_results_received_count'] = "N/S";
		$mohData['8_10_parasitological_specimen_referred_count'] = "N/S";
		$mohData['8_10_parasitological_referred_results_received_count'] = "N/S";
		$mohData['8_11_blood_for_transfusion_screening_specimen_referred_count'] = "N/S";
		$mohData['8_11_blood_for_transfusion_screening_referred_results_received_count'] = "N/S";

		$mohData['9_1_ampicilin_sensitive'] = "N/S";
		$mohData['9_1_amplicilin_resistant'] = "N/S";
		$mohData['9_1_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_1_chloramphenicol_resistant'] = "N/S";
		$mohData['9_1_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_1_ceftriaxone_resistant'] = "N/S";
		$mohData['9_1_penicilin_sensitive'] = "N/S";
		$mohData['9_1_penicilin_resistant'] = "N/S";
		$mohData['9_1_oxacillin_sensitive'] = "N/S";
		$mohData['9_1_oxacillin_resistant'] = "N/S";
		$mohData['9_1_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_1_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_1_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_1_naladixic_acid_resistant'] = "N/S";
		$mohData['9_1_trimethoprim_sensitive'] = "N/S";
		$mohData['9_1_trimethoprim_resistant'] = "N/S";
		$mohData['9_1_tetracycline_sensitive'] = "N/S";
		$mohData['9_1_tetracycline_resistant'] = "N/S";
		$mohData['9_1_augumentin_sensitive'] = "N/S";
		$mohData['9_1_augumentin_resistant'] = "N/S";
		$mohData['9_2_ampicilin_sensitive'] = "N/S";
		$mohData['9_2_amplicilin_resistant'] = "N/S";
		$mohData['9_2_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_2_chloramphenicol_resistant'] = "N/S";
		$mohData['9_2_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_2_ceftriaxone_resistant'] = "N/S";
		$mohData['9_2_penicilin_sensitive'] = "N/S";
		$mohData['9_2_penicilin_resistant'] = "N/S";
		$mohData['9_2_oxacillin_sensitive'] = "N/S";
		$mohData['9_2_oxacillin_resistant'] = "N/S";
		$mohData['9_2_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_2_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_2_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_2_naladixic_acid_resistant'] = "N/S";
		$mohData['9_2_trimethoprim_sensitive'] = "N/S";
		$mohData['9_2_trimethoprim_resistant'] = "N/S";
		$mohData['9_2_tetracycline_sensitive'] = "N/S";
		$mohData['9_2_tetracycline_resistant'] = "N/S";
		$mohData['9_2_augumentin_sensitive'] = "N/S";
		$mohData['9_2_augumentin_resistant'] = "N/S";
		$mohData['9_3_ampicilin_sensitive'] = "N/S";
		$mohData['9_3_amplicilin_resistant'] = "N/S";
		$mohData['9_3_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_3_chloramphenicol_resistant'] = "N/S";
		$mohData['9_3_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_3_ceftriaxone_resistant'] = "N/S";
		$mohData['9_3_penicilin_sensitive'] = "N/S";
		$mohData['9_3_penicilin_resistant'] = "N/S";
		$mohData['9_3_oxacillin_sensitive'] = "N/S";
		$mohData['9_3_oxacillin_resistant'] = "N/S";
		$mohData['9_3_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_3_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_3_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_3_naladixic_acid_resistant'] = "N/S";
		$mohData['9_3_trimethoprim_sensitive'] = "N/S";
		$mohData['9_3_trimethoprim_resistant'] = "N/S";
		$mohData['9_3_tetracycline_sensitive'] = "N/S";
		$mohData['9_3_tetracycline_resistant'] = "N/S";
		$mohData['9_3_augumentin_sensitive'] = "N/S";
		$mohData['9_3_augumentin_resistant'] = "N/S";
		$mohData['9_4_ampicilin_sensitive'] = "N/S";
		$mohData['9_4_amplicilin_resistant'] = "N/S";
		$mohData['9_4_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_4_chloramphenicol_resistant'] = "N/S";
		$mohData['9_4_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_4_ceftriaxone_resistant'] = "N/S";
		$mohData['9_4_penicilin_sensitive'] = "N/S";
		$mohData['9_4_penicilin_resistant'] = "N/S";
		$mohData['9_4_oxacillin_sensitive'] = "N/S";
		$mohData['9_4_oxacillin_resistant'] = "N/S";
		$mohData['9_4_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_4_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_4_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_4_naladixic_acid_resistant'] = "N/S";
		$mohData['9_4_trimethoprim_sensitive'] = "N/S";
		$mohData['9_4_trimethoprim_resistant'] = "N/S";
		$mohData['9_4_tetracycline_sensitive'] = "N/S";
		$mohData['9_4_tetracycline_resistant'] = "N/S";
		$mohData['9_4_augumentin_sensitive'] = "N/S";
		$mohData['9_4_augumentin_resistant'] = "N/S";
		$mohData['9_5_ampicilin_sensitive'] = "N/S";
		$mohData['9_5_amplicilin_resistant'] = "N/S";
		$mohData['9_5_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_5_chloramphenicol_resistant'] = "N/S";
		$mohData['9_5_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_5_ceftriaxone_resistant'] = "N/S";
		$mohData['9_5_penicilin_sensitive'] = "N/S";
		$mohData['9_5_penicilin_resistant'] = "N/S";
		$mohData['9_5_oxacillin_sensitive'] = "N/S";
		$mohData['9_5_oxacillin_resistant'] = "N/S";
		$mohData['9_5_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_5_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_5_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_5_naladixic_acid_resistant'] = "N/S";
		$mohData['9_5_trimethoprim_sensitive'] = "N/S";
		$mohData['9_5_trimethoprim_resistant'] = "N/S";
		$mohData['9_5_tetracycline_sensitive'] = "N/S";
		$mohData['9_5_tetracycline_resistant'] = "N/S";
		$mohData['9_5_augumentin_sensitive'] = "N/S";
		$mohData['9_5_augumentin_resistant'] = "N/S";
		$mohData['9_6_ampicilin_sensitive'] = "N/S";
		$mohData['9_6_amplicilin_resistant'] = "N/S";
		$mohData['9_6_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_6_chloramphenicol_resistant'] = "N/S";
		$mohData['9_6_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_6_ceftriaxone_resistant'] = "N/S";
		$mohData['9_6_penicilin_sensitive'] = "N/S";
		$mohData['9_6_penicilin_resistant'] = "N/S";
		$mohData['9_6_oxacillin_sensitive'] = "N/S";
		$mohData['9_6_oxacillin_resistant'] = "N/S";
		$mohData['9_6_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_6_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_6_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_6_naladixic_acid_resistant'] = "N/S";
		$mohData['9_6_trimethoprim_sensitive'] = "N/S";
		$mohData['9_6_trimethoprim_resistant'] = "N/S";
		$mohData['9_6_tetracycline_sensitive'] = "N/S";
		$mohData['9_6_tetracycline_resistant'] = "N/S";
		$mohData['9_6_augumentin_sensitive'] = "N/S";
		$mohData['9_6_augumentin_resistant'] = "N/S";
		$mohData['9_7_ampicilin_sensitive'] = "N/S";
		$mohData['9_7_amplicilin_resistant'] = "N/S";
		$mohData['9_7_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_7_chloramphenicol_resistant'] = "N/S";
		$mohData['9_7_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_7_ceftriaxone_resistant'] = "N/S";
		$mohData['9_7_penicilin_sensitive'] = "N/S";
		$mohData['9_7_penicilin_resistant'] = "N/S";
		$mohData['9_7_oxacillin_sensitive'] = "N/S";
		$mohData['9_7_oxacillin_resistant'] = "N/S";
		$mohData['9_7_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_7_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_7_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_7_naladixic_acid_resistant'] = "N/S";
		$mohData['9_7_trimethoprim_sensitive'] = "N/S";
		$mohData['9_7_trimethoprim_resistant'] = "N/S";
		$mohData['9_7_tetracycline_sensitive'] = "N/S";
		$mohData['9_7_tetracycline_resistant'] = "N/S";
		$mohData['9_7_augumentin_sensitive'] = "N/S";
		$mohData['9_7_augumentin_resistant'] = "N/S";
		$mohData['9_8_ampicilin_sensitive'] = "N/S";
		$mohData['9_8_amplicilin_resistant'] = "N/S";
		$mohData['9_8_chloramphenicol_sensitive'] = "N/S";
		$mohData['9_8_chloramphenicol_resistant'] = "N/S";
		$mohData['9_8_ceftriaxone_sensitive'] = "N/S";
		$mohData['9_8_ceftriaxone_resistant'] = "N/S";
		$mohData['9_8_penicilin_sensitive'] = "N/S";
		$mohData['9_8_penicilin_resistant'] = "N/S";
		$mohData['9_8_oxacillin_sensitive'] = "N/S";
		$mohData['9_8_oxacillin_resistant'] = "N/S";
		$mohData['9_8_ciprofloxacin_sensitive'] = "N/S";
		$mohData['9_8_ciprofloxacin_resistant'] = "N/S";
		$mohData['9_8_naladixic_acid_sensitive'] = "N/S";
		$mohData['9_8_naladixic_acid_resistant'] = "N/S";
		$mohData['9_8_trimethoprim_sensitive'] = "N/S";
		$mohData['9_8_trimethoprim_resistant'] = "N/S";
		$mohData['9_8_tetracycline_sensitive'] = "N/S";
		$mohData['9_8_tetracycline_resistant'] = "N/S";
		$mohData['9_8_augumentin_sensitive'] = "N/S";
		$mohData['9_8_augumentin_resistant'] = "N/S";

		return View::make('reports.moh.706v201410')->with('mohData', $mohData)->with('startDate', $startDate)->with('endDate', $endDate);
	}

	/**
	 * Manage Diseases reported on
	 * @param
	 */
	public function disease(){
		if (Input::all()) {
			$rules = array();
			$newDiseases = Input::get('new-diseases');

			if (Input::get('new-diseases')) {
				// create an array that form the rules array
				foreach ($newDiseases as $key => $value) {
					
					//Ensure no duplicate disease
					$rules['new-diseases.'.$key.'.disease'] = 'unique:diseases,name';
				}
			}

			$validator = Validator::make(Input::all(), $rules);

			if ($validator->fails()) {
				return Redirect::route('reportconfig.disease')->withErrors($validator);
			} else {

		        $allDiseaseIds = array();
				
				//edit or leave disease entries as is
				if (Input::get('diseases')) {
					$diseases = Input::get('diseases');

					foreach ($diseases as $id => $disease) {
		                $allDiseaseIds[] = $id;
						$diseases = Disease::find($id);
						$diseases->name = $disease['disease'];
						$diseases->save();
					}
				}
				
				//save new disease entries
				if (Input::get('new-diseases')) {
					$diseases = Input::get('new-diseases');

					foreach ($diseases as $id => $disease) {
						$diseases = new Disease;
						$diseases->name = $disease['disease'];
						$diseases->save();
		                $allDiseaseIds[] = $diseases->id;
					}
				}

		        //check if action is from a form submission
		        if (Input::get('from-form')) {
			     	// Delete any pre-existing disease entries
			     	//that were not captured in any of the above save loops
			        $allDiseases = Disease::all(array('id'));

			        $deleteDiseases = array();

			        //Identify disease entries to be deleted by Ids
			        foreach ($allDiseases as $key => $value) {
			            if (!in_array($value->id, $allDiseaseIds)) {

							//Allow delete if not in use
							$inUseByReports = Disease::find($value->id)->reportDiseases->toArray();
							if (empty($inUseByReports)) {
							    
							    // The disease is not in use
			                	$deleteDiseases[] = $value->id;
							}
			            }
			        }
			        //Delete disease entry if any
			        if(count($deleteDiseases)>0){

			        	Disease::destroy($deleteDiseases);
			        }
		        }
			}
		}
		$diseases = Disease::all();

		return View::make('reportconfig.disease')
					->with('diseases', $diseases);
	}

	public function stockLevel(){
		
		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;

		$reportTypes = array('Monthly', 'Quarterly');		
		$items = Item::lists( 'name', 'id');		
		$selectedItem = Input::get('search_item_id');	

		if($from||$to){

			if(!$to) $to = $date;

			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
					$error = trans('messages.check-date-range');					
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));

				// to be displayed by default when opening the supply report
				if( $selectedItem)
				{ 
					$supplyData=Stock::where('item_id',  $selectedItem)->whereBetween('created_at', array($from, $toPlusOne->format('Y-m-d H:i:s')))->get();
							
				}else{
					$supplyData=Stock::whereBetween('created_at', array($from, $toPlusOne->format('Y-m-d H:i:s')))->get();
				}		
			}
		}	
		$reportTitle = Lang::choice('messages.monthly-stock-level-report-title',1);
		$reportTitle = str_replace("[FROM]", $from, $reportTitle);
		$reportTitle = str_replace("[TO]", $to, $reportTitle);
		
		return View::make('reports.inventory.supply')
		->with('reportTypes', $reportTypes)
		->with('supplyData', $supplyData)
		->with('reportTitle', $reportTitle)
		->with('items', $items)
		->withInput(Input::all());		
	}

	public function usageLevel(){
		
		//	Fetch form filters
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');

		$to = Input::get('end');
		if(!$to) $to = $date;
		
		$reportTypes = array('Monthly', 'Quarterly');		
		$items = Item::lists( 'name', 'id');

		$selectedReport = Input::get('report_type');	
		$selectedItem = Input::get('search_item_id');	
		$selected_record_type = Input::get('records');

		$usageData = null;

		if($from||$to){			

			if(strtotime($from)>strtotime($to)||strtotime($from)>strtotime($date)||strtotime($to)>strtotime($date)){
					$error = trans('messages.check-date-range');					
			}
			else
			{
				$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));
				if ($selectedItem) {
					$stock = Stock::where('item_id',$selectedItem)->get();

					//If a particular item is chosen, loop through eachone in the supply/stock table to see the records of each item's usage
					foreach ($stock as $key => $stock_item) {
							
						$usageData=	$stock_item->usage()->whereBetween('created_at', array($from, $toPlusOne->format('Y-m-d H:i:s')))->get();
					}
				}
				else
				{
					//If no item was selected, display all items usage by default.
					$usageData=Usage::whereBetween('created_at', array($from, $toPlusOne->format('Y-m-d H:i:s')))->get();
				}			
			}
		}		
		$reportTitle = Lang::choice('messages.monthly-stock-level-report-title',1);
		$reportTitle = str_replace("[FROM]", $from, $reportTitle);
		$reportTitle = str_replace("[TO]", $to, $reportTitle);
		
		return View::make('reports.inventory.index')
				->with('reportTypes', $reportTypes)
				->with('reportData', $usageData)
				->with('reportTitle', $reportTitle)
				->with('items', $items)
				->withInput(Input::all());		
	}

	/*
	 *	Function to autoload items from the database
	 */

	public function autoComplete() {
        $term = Input::get('term');
	
		$results = array();
		
		$queries = DB::table('inv_items')
			->where('name', 'LIKE', '%'.$term.'%')
			->take(5)->get();
		
		foreach ($queries as $query)
		{
		    $results[] = [ 'id' => $query->id, 'value' => $query->name];
		}
		if (empty($results)>0) {
			# code...
		    $results[] = [ 'id' => 0, 'value' => 'No Records found'];
		} 
		return Response::json($results);
       
    }
		
	/**
	 * Function to calculate the mean, SD, and UCL, LCL
	 * for a given control measure.
	 *
	 * @param control_measure_id
	 * @return json string
	 * 
	 */
	public function leveyJennings($control, $dates)
	{
		foreach ($control->controlMeasures as $key => $controlMeasure) {
			if(!$controlMeasure->isNumeric())
			{
				//We ignore non-numeric results
				continue;
			}

			$results = $controlMeasure->results()->whereBetween('created_at', $dates)->lists('results');

			$count = count($results);

			if($count < 6)
			{
				$response[] = array('success' => false,
					'error' => "Too few results to create LJ for ".$controlMeasure->name);
				continue;
			}

			//Convert string results to float 
			foreach ($results as &$result) {
				$result = (double) $result;
			}

			$total = 0;
			foreach ($results as $res) {
				$total += $res;
			}

			$average = round($total / $count, 2);

			$standardDeviation = $this->stat_standard_deviation($results);
			$standardDeviation  = round($standardDeviation, 2);

			$response[] = array('success' => true,
							'total' => $total,
							'average' => $average,
							'standardDeviation' => $standardDeviation,
							'plusonesd' => $average + $standardDeviation,
							'plustwosd' => $average + ($standardDeviation * 2),
							'plusthreesd' => $average + ($standardDeviation * 3),
							'minusonesd' => $average - ($standardDeviation),
							'minustwosd' => $average - ($standardDeviation * 2),
							'minusthreesd' => $average - ($standardDeviation * 3),
							'dates' => $controlMeasure->results()->lists('created_at'),
							'controlName' => $controlMeasure->name,
							'controlUnit' => $controlMeasure->unit,
							'results' => $results);
		}
		return json_encode($response);
	}

    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     * 
     * @param array $a 
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stat_standard_deviation(array $a, $sample = false) {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        return sqrt($carry / $n);
    }

	/**
	 * Display data after applying the filters on the report uses patient ID
	 *
	 * @return Response
	 */
	public function cd4(){
		//	check if accredited
		$accredited = array();
		$from = Input::get('start');
		$to = Input::get('end');
		$pending = Input::get('pending');
		$date = date('Y-m-d');
		$error = '';
		//	Check dates
		if(!$from)
			$from = date('Y-m-01');
		if(!$to)
			$to = $date;
		//	Get columns
		$columns = array(Lang::choice('messages.cd4-less', 1), Lang::choice('messages.cd4-greater', 1));
		$rows = array(Lang::choice('messages.baseline', 1), Lang::choice('messages.follow-up', 1));
		//	Get test
		$test = TestType::find(TestType::getTestTypeIdByTestName('cd4'));
		$counts = array();
		foreach ($columns as $column)
		{
			foreach ($rows as $row)
			{
				if($test != null) {
					$counts[$column][$row] = $test->cd4($from, $to, $column, $row);
				}
				else {
					$counts[$column][$row] = 0;
				}
			}
		}
		if(Input::has('word'))
		{
			$date = date("Ymdhi");
			$fileName = "cd4_report_".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.cd4.export')
				->with('columns', $columns)
				->with('rows', $rows)
				->with('accredited', $accredited)
				->with('test', $test)
				->with('counts', $counts)
				->withInput(Input::all());
	    	return Response::make($content,200, $headers);
		}
		else
		{
			return View::make('reports.cd4.index')
				->with('columns', $columns)
				->with('rows', $rows)
				->with('accredited', $accredited)
				->with('test', $test)
				->with('counts', $counts)
				->withInput(Input::all());
		}
	}

    /**
     *	Function to check for accredited test types
     *
     */
    public function accredited($tests)
    {
    	$accredited = array();
		foreach ($tests as $test) {
			if($test->testType->isAccredited())
				array_push($accredited, $test->id);
		}
		return $accredited;
    }

    /**
	 * Display specimen rejection chart
	 *
	 * @return Response
	 */
	public static function specimenRejectionChart($testTypeID = 0){
		$from = Input::get('start');
		$to = Input::get('end');
		$spec_type = Input::get('specimen_type');
		$months = json_decode(self::getMonths($from, $to));

		//	Get specimen rejection reasons available in the time period
		$rr = Specimen::select(DB::raw('DISTINCT(reason) AS rr, rejection_reason_id'))
						->join('rejection_reasons', 'rejection_reasons.id', '=', 'specimens.rejection_reason_id')
						->whereBetween('time_rejected', [$from, $to])
						->groupBy('rr')
						->get();

		$options = '{
		    "chart": {
		        "type": "spline"
		    },
		    "title": {
		        "text":"Rejected Specimen per Reason Overtime"
		    },
		    "subtitle": {
		        "text":'; 
		        if($from==$to)
		        	$options.='"'.trans('messages.for-the-year').' '.date('Y').'"';
		        else
		        	$options.='"'.trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to.'"';
		    $options.='},
		    "credits": {
		        "enabled": false
		    },
		    "navigation": {
		        "buttonOptions": {
		            "align": "right"
		        }
		    },
		    "series": [';
		    	$counts = count($rr);

			    	foreach ($rr as $rrr) 
			    	{
		        		$options.= '{
		        			"name": "'.$rrr->rr.'","data": [';
	        				$counter = count($months);
	            			foreach ($months as $month) 
	            			{
		            			$data = Specimen::where('rejection_reason_id', $rrr->rejection_reason_id)->whereRaw('MONTH(time_rejected)='.$month->months);
		            			if($spec_type)
		            				$data = $data->where('specimen_type_id', $spec_type);
		            			$data = $data->count();		            				
            					$options.= $data;
            					if($counter==1)
	            					$options.='';
	            				else
	            					$options.=',';
		            			$counter--;
				    		}
				    		$options.=']';
				    	if($counts==1)
							$options.='}';
						else
							$options.='},';
						$counts--;
					}
			$options.='],
		    "xAxis": {
		        "categories": [';
		        $count = count($months);
	            	foreach ($months as $month) {
	    				$options.= '"'.$month->label." ".$month->annum;
	    				if($count==1)
	    					$options.='" ';
	    				else
	    					$options.='" ,';
	    				$count--;
	    			}
	            $options.=']
		    },
		    "yAxis": {
		        "title": {
		            "text": "No. of Rejected Specimen"
		        }
		    }
		}';
		return View::make('reports.rejection.index')
						->with('options', $options)
						->withInput(Input::all());
	}

	public function critical()
	{
		$date = date('Y-m-d');
		$from = Input::get('start');
		if(!$from) $from = date('Y-m-01');
		$to = Input::get('end');
		if(!$to) $to = $date;
		$toPlusOne = date_add(new DateTime($to), date_interval_create_from_date_string('1 day'));

		$ageRanges = array('0-5', '5-15', '15-120');	//	Age ranges - will definitely change in configurations
		$gender = array(Patient::MALE, Patient::FEMALE); 	//	Array for gender - male/female
		//	Get test categories with critical values
		$tc = CritVal::lists('test_category_id');
		$tc = array_unique($tc);

		if(Input::has('word'))
		{
			$date = date("Ymdhi");
			$fileName = "critical values report - ".$date.".doc";
			$headers = array(
			    "Content-type"=>"text/html",
			    "Content-Disposition"=>"attachment;Filename=".$fileName
			);
			$content = View::make('reports.critical.exportCritical')
						->with('gender', $gender)
						->with('ageRanges', $ageRanges)
						->with('tc', $tc)
						->with('from', $from)
						->with('to', $to)
						->with('toPlusOne', $toPlusOne);
	    	return Response::make($content,200, $headers);
		}
		else
		{		
			return View::make('reports.critical.critical')
						->with('gender', $gender)
						->with('ageRanges', $ageRanges)
						->with('tc', $tc)
						->with('from', $from)
						->with('to', $to)
						->with('toPlusOne', $toPlusOne);
		}
	}

	public function item()
	{
		$accredited = array();
		$items = Topup::all()->lists('name', 'id');
		$accredited = array();
		$tests = array();
		return View::make('reports.qualitycontrol.index')
			->with('accredited', $accredited)
			->with('tests', $tests)
			->with('controls', $controls);
	}
}
