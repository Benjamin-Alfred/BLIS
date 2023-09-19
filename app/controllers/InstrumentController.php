<?php

use Illuminate\Database\QueryException;

/**
 *Contains functions for managing instruments
 *
 */
class InstrumentController extends \BaseController {

	/**
	 * Display a listing of the instruments.
	 *
	 * @return Response
	 */
	public function index()
	{
		// List all the active instruments
			$instruments = Instrument::paginate(Config::get('kblis.page-items'));

		// Load the view and pass the instruments
		return View::make('instrument.index')->with('instruments', $instruments);
	}

	/**
	 * Show the form for creating a new instrument.
	 *
	 * @return Response
	 */
	public function create()
	{
		// Get a list of all installed plugins
		$plugins = Instrument::getInstalledPlugins();

		//Create Instrument view
		return View::make('instrument.create')->with('instruments', $plugins);
	}

	/**
	 * Store a newly created instrument in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
		$rules = array(
			'name' => 'required',
			'ip' => 'sometimes|ip',
		);
		$validator = Validator::make(Input::all(), $rules);

		// Validate form input
		if ($validator->fails()) {
			return Redirect::route('instrument.create')->withErrors($validator);
		} else {
			// Save the instrument
			$newInstrument = new Instrument();
			$newInstrument->name = Input::get('name');
			$newInstrument->description = Input::get('description');
			$newInstrument->ip = Input::get('ip');
			$newInstrument->hostname = Input::get('hostname');

			$newInstrument->save();
			return Redirect::route('instrument.index')->with('message', trans('messages.success-creating-instrument'));
		}
	}

	/**
	 * Display the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//Show an instrument
		$instrument = Instrument::find($id);

		//Show the view and pass the $instrument to it
		return View::make('instrument.show')->with('instrument', $instrument);
	}

	/**
	 * Show the form for editing the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//Get the instrument
		$instrument = Instrument::find($id);

		//Open the Edit View and pass to it the $instrument
		return View::make('instrument.edit')->with('instrument', $instrument);
	}

	/**
	 * Update the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
		$rules = array(
			'name' => 'required',
			'ip' => 'required|ip'
		);
		$validator = Validator::make(Input::all(), $rules);

		// process the login
		if ($validator->fails()) {
			return Redirect::back()->withErrors($validator);
		} else {
			// Update
			$instrument = Instrument::find($id);
			$instrument->name = Input::get('name');
			$instrument->description = Input::get('description');
			$instrument->ip = Input::get('ip');
			$instrument->hostname = Input::get('hostname');

			try{
				$instrument->save();
				$message = trans('messages.success-updating-instrument');
			}catch(QueryException $e){
				$message = trans('messages.failure-updating-instrument');
				Log::error($e);
			}

			return Redirect::route('instrument.index')->with('message', $message);
		}
	}

	/**
	 * Remove the specified instrument from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

	/**
	 * Remove the specified instruments from storage (global UI implementation).
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function delete($id)
	{
		//Delete the instrument
		$instrument = Instrument::find($id);
 
		$instrument->testTypes()->detach();
		$instrument->delete();

		// redirect
		return Redirect::route('instrument.index')->with('message', trans('messages.success-deleting-instrument'));
	}

	/**
	 * Pull test results from an instrument as JSON.
	 *
	 * @return Response
	 */
	public function getTestResult()
	{

		try {
			
			//Get Instrument Interface Class file
			$testTypeID = Input::get("test_type_id");
			$specimenID = Input::get("specimen_id");
			$testType = TestType::find($testTypeID);
			$resultFile = storage_path()."/uploads/".basename($_FILES["file-to-fetch"]["name"]);

			Input::file('file-to-fetch')->move(storage_path().'/uploads/', basename($_FILES["file-to-fetch"]["name"]));

			$instrument = $testType->instruments->filter(function($inst){
					return $inst->active == 1;
				})->first();

	 		// Fetch the results
			$result = $instrument->fetchResult($testType, $specimenID, $resultFile);
		} catch (Exception $e) {
			\Log::error($e);
			$result = "";
		}

		return $result;
	}

	/**
	 * Pull test results from an instrument as JSON. /For controls
	 *
	 * @return Response
	 */
	public function getControlResult()
	{
		$controlID = Input::get("control_id");
		$control = Control::find($controlID);
		$instrument = Instrument::where('name','Celltac F Mek 8222')->first();
 		// Fetch the results
		return $instrument->fetchControlResult($control);
	}

	/**
	 * Save an imported implemention of the Intrumentation class.
	 *
	 * @param String route
	 * @return Response
	 */
	public function importDriver()
	{
		$route = (Input::get('import_file') !== null)?Input::get('import_file'):"instrument.index";

        $rules = array(
            'import_file' => 'required|max:500'
        );

        $validator = Validator::make(Input::all(), $rules);
        $message = null;

        if ($validator->fails()) {
            return Redirect::route('instrument.index')->withErrors($validator);
        } else {
            if (Input::hasFile('import_file')) {
            	$message = Instrument::saveDriver(Input::file('import_file'));
            }
        }

		return Redirect::route($route)->with('message', $message);
	}

	/**
	 * Show the form for editing the specified instrument testype measure mappings.
	 *
	 * @param  int  $instrument_id
	 * @param  int  $testtype_id
	 * @return Response
	 */
	public function viewMapping($instrumentID, $testtypeID)
	{
		//Get the instrument
		$instrument = Instrument::find($instrumentID);
		$testType = TestType::find($testtypeID);
		$mappings = DB::table('instrument_testtype_measure_mappings')
						->select('instrument_id', 'testtype_id', 'measure_id', 'mapping')
						->where('instrument_id',$instrumentID)
						->where('testtype_id', $testtypeID)->get();

		//Open the Edit View and pass to it the $instrument
		return View::make('instrument.mapping')
						->with('instrument', $instrument)
						->with('testType', $testType)
						->with('mappings', $mappings);
	}

	/**
	 * Save instrument-testtype-measure mapping.
	 *
	 * @return Response
	 */
	public function saveMapping()
	{
		$instrumentID = Input::get('instrument_id');
		$testTypeID = Input::get('testtype_id');
		$allInputFields = Input::all();
		$measureMappings = array();

		if(is_array($allInputFields)){
			foreach ($allInputFields as $key => $value) {
				if (strcmp(substr($key, 0, 2), "m_") == 0) {
					// code...
					$measureMappings[] = array(
						'instrument_id' => (int)$instrumentID,
						'testtype_id' => (int)$testTypeID,
						'measure_id' => (int)str_replace("m_", "", $key),
						'mapping' => trim($value),
						);
				}
			}
		}

		if (!empty($measureMappings)) {
			// Delete existing instrument test_type mappings
			DB::table('instrument_testtype_measure_mappings')
					->where('instrument_id', '=', $instrumentID)
					->where('testtype_id', '=', $testTypeID)->delete();
			// Add the new mapping
			DB::table('instrument_testtype_measure_mappings')->insert($measureMappings);
			$message = trans('messages.success-updating-instrument-testtypes');
		}else{

			$message = trans('messages.failure-updating-instrument-testtypes');
		}

		return Redirect::action('InstrumentController@show', array($instrumentID))->with('message', $message);
	}

	/**
	 * Show the form for editing the specified instrument.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addTestTypes($id)
	{
		//Get the instrument
		$instrument = Instrument::find($id);
		$testTypes = TestType::all()->sortBy('name');

		//Open the testtypes View and pass to it the $instrument
		return View::make('instrument.testtypes')->with('instrument', $instrument)->with('testtypes', $testTypes);
	}

	/**
	 * Update the specified instrument testtypes.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function updateTestTypes($id)
	{
	
		$testTypesAdded = array();
		$testTypes = Input::get('testtypes');

		if(is_array($testTypes)){
			foreach ($testTypes as $key => $value) {
				$testTypesAdded[] = array(
					'instrument_id' => (int)$id,
					'test_type_id' => (int)$value,
					);
			}

		}

		if (!empty($testTypes)) {
			// Delete existing instrument test_type mappings
			DB::table('instrument_testtypes')->where('instrument_id', '=', $id)->delete();
			// Add the new mapping
			DB::table('instrument_testtypes')->insert($testTypesAdded);
			$message = trans('messages.success-updating-instrument-testtypes');
		}else{

			$message = trans('messages.failure-updating-instrument-testtypes');
		}


		return Redirect::action('InstrumentController@show', array($id))->with('message', $message);
	}

	/**
	 * Remove the specified instrument testtype.
	 *
	 * @param  int  $instrumentID
	 * @param  int  $testTypeID
	 * @return Response
	 */
	public function deleteTestType($instrumentID, $testTypeID)
	{
		//Delete the instrument
		$instrument = Instrument::find($instrumentID);
 
		$instrument->testTypes()->detach($testTypeID);

		// redirect
		return Redirect::action('InstrumentController@show', array($instrumentID))->with('message', trans('messages.success-deleting-test-type'));
	}
}
