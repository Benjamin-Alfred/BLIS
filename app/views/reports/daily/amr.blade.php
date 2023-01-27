@extends("layout")
@section("content")
<div>
	<ol class="breadcrumb">
	  <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
	  <li class="active"><a href="{{ URL::route('reports.patient.index') }}">{{ Lang::choice('messages.report', 2) }}</a></li>
	  <li class="active">{{ trans('messages.daily-log') }}</li>
	</ol>
</div>
<div class='container-fluid'>
{{ Form::open(array('route' => array('reports.daily.log'), 'class' => 'form-inline', 'role' => 'form')) }}
	<div class='row'>
		<div class="col-sm-4">
	    	<div class="row">
				<div class="col-sm-2">
				    {{ Form::label('start', trans('messages.from')) }}
				</div>
				<div class="col-sm-2">
				    {{ Form::text('start', isset($input['start'])?$input['start']:date('Y-m-d'), 
			                array('class' => 'form-control standard-datepicker')) }}
		        </div>
			</div>
		</div>
		<div class="col-sm-4">
	    	<div class="row">
				<div class="col-sm-2">
				    {{ Form::label('end', trans('messages.to')) }}
				</div>
				<div class="col-sm-2">
				    {{ Form::text('end', isset($input['end'])?$input['end']:date('Y-m-d'), 
			                array('class' => 'form-control standard-datepicker')) }}
		        </div>
			</div>
		</div>
		<div class="col-sm-4">
	    	<div class="row">
				<div class="col-sm-3">
				  	{{ Form::button("<span class='glyphicon glyphicon-filter'></span> ".trans('messages.view'), 
		                array('class' => 'btn btn-info', 'id' => 'filter', 'type' => 'submit')) }}
		        </div>
		        <div class="col-sm-1">
		        	<input type="hidden" name="word" id="word">
					{{Form::button("<span class='glyphicon glyphicon-export'></span> ".trans('messages.export'), 
			    		array('class' => 'btn btn-success', 'onclick' => 'document.getElementById("word").value="word"', 'type'=>'submit'))}}
				</div>
			</div>
		</div>
	</div>
	<div class='row spacer'>
	    <div class="col-sm-12">
	    	<div class="row">
				<div class="col-sm-3">
				  	<label class="radio-inline">
				    	<?php
				    		$records = strcmp("tests", Input::old('records')) == 0?"true":"false";
				    	?>
						{{ Form::radio('records', 'tests', $records, array('data-toggle' => 'radio', 
						  'id' => 'tests')) }} {{trans('messages.test-records')}}
					</label>
				</div>
				<div class="col-sm-3">
				    <label class="radio-inline">
				    	<?php
				    		$records = strcmp("patients", Input::old('records')) == 0?"true":"false";
				    	?>
						{{ Form::radio('records', 'patients', $records, array('data-toggle' => 'radio',
						  'id' => 'patients', Entrust::can('can_access_ccc_reports')?'disabled':'' )) }} {{trans('messages.patient-records')}}
					</label>
				</div>
				<div class="col-sm-3">
				    <label class="radio-inline">
				    	<?php
				    		$records = strcmp("rejections", Input::old('records')) == 0?"true":"false";
				    	?>
						{{ Form::radio('records', 'rejections', $records, array('data-toggle' => 'radio',
						  'id' => 'specimens', Entrust::can('can_access_ccc_reports')?'disabled':'' )) }} {{trans('messages.rejected-specimen')}}
					</label>
				</div>
				<div class="col-sm-3">
				    <label class="radio-inline">
				    	<?php
				    		$records = strcmp("amr-tests", Input::old('records')) == 0?"true":"false";
				    	?>
						{{ Form::radio('records', 'amr-tests', $records, array('data-toggle' => 'radio',
						  'id' => 'amr-tests', Entrust::can('can_access_ccc_reports')?'disabled':'' )) }} AMR Tests
					</label>
				</div>
			</div>
	    	<div class="row" id="tests-div">
				<div class="col-sm-4">
					<label class="radio-inline">
				    	<?php
				    		$pend = strcmp("pending", Input::old('pending_or_all')) == 0?"true":"false";
				    	?>
			    		{{ Form::radio('pending_or_all', 'pending', $pend, array('data-toggle' => 'radio',
						'id' => 'pending')) }} {{trans('messages.pending-tests')}}
					</label>
				</div>
				<div class="col-sm-4">
					<label class="radio-inline">
						{{ Form::radio('pending_or_all', 'complete', false, array('data-toggle' => 'radio',
						'id' => 'pending')) }} {{trans('messages.complete-tests')}}
					</label>
				</div>
				<div class="col-sm-4">
				    <label class="radio-inline">
				    	{{ Form::radio('pending_or_all', 'all', false, array('data-toggle' => 'radio',
						  'id' => 'all')) }} {{trans('messages.all-tests')}}
					</label>
				</div>
		  	</div>
		</div>
	</div>
{{ Form::close() }}
</div>
<br />
<div class='panel panel-primary'>
@if ($error!='')
		<!-- if there are search errors, they will show here -->
	<div class="alert alert-info">{{ $error }}</div>
@else
	<div class="panel-heading">
		<span class="glyphicon glyphicon-user"></span>
		{{ trans('messages.daily-log') }} - {{trans('messages.patient-records')}}
	</div>
	<div  class="panel-body">
	<div class="table-responsive" style="overflow: scroll;">
	  	<table class="table table-bordered">
			<tbody>
				<tr>
					<th>{{trans('messages.patient-name')}}</th>
					<th>IP/OP Number</th>
					<th>{{trans('messages.gender')}}</th>
					<th>DOB</th>
					<th>{{trans('messages.age')}}</th>
					<th>Country</th>
					<th>County</th>
					<th>Sub-county</th>
					<th>Diagnosis</th>
					<th>Date of specimen collection</th>
					<th>Location</th>
					<th>Department</th>
					<th>Date of Admission</th>
					<th>Prior Antibiotic Therapy</th>
					<th>{{trans('messages.specimen-type-title')}}</th>
					<th>Specimen Site</th>
					<th>Lab ID</th>
					<th>Isolates Obtained?</th>
					<th>Isolate Name</th>
					<th>Test Method</th>
					<th>Gram Pos/Neg</th>
					<th>Drugs Tested</th>
					<th>Zone (mm)</th>
					<th>Interpretation (SIR)</th>
					<th>{{ Lang::choice('messages.test', 2) }}</th>
				</tr>
				@forelse($tests as $test)
				<?php
					$externalDump = ExternalDump::where('lab_no', '=', $test->external_lab_id)->where('test_id', '=', $test->id)->get()->first();
					$location = explode("|", $externalDump->city);
					$sizeOfLocation = sizeof($location);
					$remarks = explode("|", $externalDump->system_id);
				?>
				<tr>
					<td>{{ $test->visit->patient->name }}</td>
					<td>{{ $test->visit->visit_number }}</td>
					<td>{{ $test->visit->patient->getGender()}}</td>
					<td>{{ $test->visit->patient->dob }}</td>
					<td>{{ $test->visit->patient->getAge("Y") }} years</td>
					<td>&nbsp;</td>
					<td><?php echo $sizeOfLocation > 3?$location[0]:'';?></td>
					<td><?php echo $sizeOfLocation > 3?$location[1]:'';?></td>
					<td>{{ $externalDump->provisional_diagnosis }}</td>
					<td>{{ $test->specimen->time_accepted }}</td>
					<td>{{ $test->visit->visit_type }}</td>
					<td><?php echo $sizeOfLocation == 5?$location[4]:'';?></td>
					<td>{{ $externalDump->date_of_admission }}</td>
					<td><?php echo count(remarks) > 1?$remarks[1]:'';?></td>
					<td>{{ $test->specimen->specimenType->name }}</td>
					<td>{{ $test->specimen->specimenType->name }}</td>
					<td>&nbsp;</td>
					<?php
						$isolateObtained = "";
						$isolateName = "";
						$drugTested = "";
						$zone = "";
						$sir = "";
						if (count($test->susceptibility) > 0) {
							$isolateObtained .= "<p>Yes</p>";
							$tempIsolate = "";
							foreach ($test->susceptibility as $suscept) {
								if (isset($suscept->id) && $suscept->zone > 0) {
									if(strcmp($tempIsolate, $suscept->organism->name) != 0){
										$tempIsolate = $suscept->organism->name;
										$isolateName .= "<p>".$suscept->organism->name."</p>";
									}else{
										$isolateName .= "<p>&nbsp;</p>";
									}
									$drugTested .= "<p>".$suscept->drug->name."</p>";
									$zone .= "<p>".$suscept->zone."</p>";
									$sir .= "<p>".$suscept->interpretation."</p>";
								}
					?>
					<?php
							}
						}else{
							$isolateObtained .= "<p>No</p>";
						}
					?>
					<td>{{ $isolateObtained }}</td>
					<td>{{ $isolateName }}</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>{{ $drugTested }}</td>
					<td>{{ $zone }}</td>
					<td>{{ $sir }}</td>
					<td>{{ $test->testType->name }}</td>
				</tr>
				@empty
				<tr><td colspan="13">{{trans('messages.no-records-found')}}</td></tr>
				@endforelse
			</tbody>
		</table>
	</div></div>
@endif
</div>

@stop