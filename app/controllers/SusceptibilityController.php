<?php

class SusceptibilityController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$action = Input::get('action');
		$user_id = Auth::user()->id;

		if($action == "V2-ast"){
			$testID = Input::get('test_id');
			$ast = Input::get('astarray');
			foreach ($ast as $key => $value) {
				$astArray = explode("|", $value);
				$organism = Organism::firstOrCreate(array('name' => $astArray[0]));
				$antibiotic = Drug::firstOrCreate(array('name' => $astArray[1]));
				Log::info("Antibiotic: ".$antibiotic->name);
				Log::info("Organism: ".$organism->name);
				$susceptibility = Susceptibility::firstOrCreate(array(
					'user_id' => $user_id, 
					'test_id' => $testID, 
					'organism_id' => $organism->id,
					'drug_id' => $antibiotic->id));

				$susceptibility->zone = $astArray[2];
				$susceptibility->interpretation = $astArray[3];
				$susceptibility->save();
			}

			return json_encode(array('test_id' => $testID, 'organism_id' => $organism->id));
		}else{
			$test = Input::get('test');
			$organism = Input::get('organism');
			$drug = Input::get('drug');
			$zone = Input::get('zone');
			$interpretation = Input::get('interpretation');

			for($i=0; $i<count($test); $i++){
				$sensitivity = Susceptibility::getDrugSusceptibility($test[$i], $organism[$i], $drug[$i]);
				if($zone[$i]!=NULL || $interpretation[$i]!=NULL)
				{
					if(count($sensitivity)>0){
						$drugSusceptibility = Susceptibility::find($sensitivity->id);
						$drugSusceptibility->user_id = $user_id;
						$drugSusceptibility->test_id = $test[$i];
						$drugSusceptibility->organism_id = $organism[$i];
						$drugSusceptibility->drug_id = $drug[$i];
						$drugSusceptibility->zone = $zone[$i];
						$drugSusceptibility->interpretation = $interpretation[$i];
						$drugSusceptibility->save();
					}
					else
					{
						
						$drugSusceptibility = new Susceptibility;
						$drugSusceptibility->user_id = $user_id;
						$drugSusceptibility->test_id = $test[$i];
						$drugSusceptibility->organism_id = $organism[$i];
						$drugSusceptibility->drug_id = $drug[$i];
						$drugSusceptibility->zone = $zone[$i];
						$drugSusceptibility->interpretation = $interpretation[$i];
						$drugSusceptibility->save();
					}
				}
				
			}
		}

		if ($action == "results"){
			$test_id = Input::get('testId');
			$susceptibility = Susceptibility::where('test_id', $test_id)
											->where('zone', '!=', 0)
											->get();

			foreach ($susceptibility as $drugSusceptibility) {
				$drugSusceptibility->drugName = Drug::find($drugSusceptibility->drug_id)->name;
				$drugSusceptibility->abbreviation = Drug::find($drugSusceptibility->drug_id)->abbreviation;
				$drugSusceptibility->pathogen = Organism::find($drugSusceptibility->organism_id)->name;
				if($drugSusceptibility->interpretation == 'I'){
					$drugSusceptibility->sensitivity = 'Intermediate';
				}
				else if($drugSusceptibility->interpretation == 'R'){
					$drugSusceptibility->sensitivity = 'Resistant';
				}
				else if($drugSusceptibility->interpretation == 'S'){
					$drugSusceptibility->sensitivity = 'Sensitive';
				}
			}

			return json_encode($susceptibility);
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
		$action = Input::get('action');
		$user_id = Auth::user()->id;

		if($action == "V2-ast"){
			$testID = Input::get('test_id');

			Susceptibility::where('test_id', '=', $testID)->delete();

			$ast = Input::get('astcheck');

			foreach ($ast as $key => $value) {
				$astArray = explode("|", $value);
				$organism = Organism::firstOrCreate(array('name' => $astArray[0]));
				$antibiotic = Drug::firstOrCreate(array('name' => $astArray[1]));
				$susceptibility = Susceptibility::firstOrCreate(array(
					'user_id' => $user_id, 
					'test_id' => $testID, 
					'organism_id' => $organism->id,
					'drug_id' => $antibiotic->id));

				$susceptibility->zone = $astArray[2];
				$susceptibility->interpretation = $astArray[3];
				$susceptibility->save();
			}

			return json_encode(array('test_id' => $testID, 'organism_id' => $organism->id));
		}
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
