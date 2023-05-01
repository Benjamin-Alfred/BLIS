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
		                array('class' => 'btn btn-info', 'id' => 'filter', 'onclick' => 'document.getElementById("word").value=""', 'type' => 'submit')) }}
		        </div>
		        <div class="col-sm-1">
		        	<input type="hidden" name="word" id="word">
					{{Form::button("<span class='glyphicon glyphicon-export'></span> XLS ", 
			    		array('class' => 'btn btn-success', 'onclick' => 'document.getElementById("word").value="xls"', 'type'=>'submit'))}}
<!-- 					{{Form::button("<span class='glyphicon glyphicon-export'></span> JSON ", 
			    		array('class' => 'btn btn-success', 'onclick' => 'document.getElementById("word").value="json"', 'type'=>'submit'))}} -->
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
					<th>MIC</th>
					<th>Interpretation (SIR)</th>
					<th>{{ Lang::choice('messages.test', 2) }}</th>
				</tr>
				@forelse($testContent as $tc)
				@if(count($tc) > 1)
				<tr>
					<td>{{ $tc['patient_name'] }}</td>
					<td>{{ $tc['patient_number'] }}</td>
					<td>{{ $tc['gender']}}</td>
					<td>{{ $tc['dob'] }}</td>
					<td>{{ $tc['age'] }} years</td>
					<td>&nbsp;</td>
					<td>{{ $tc["county"] }}</td>
					<td>{{ $tc["sub_county"] }}</td>
					<td>{{ $tc["prediagnosis"] }}</td>
					<td>{{ substr($tc['specimen_collection_date'],0,10) }}</td>
					<td>{{ $tc['patient_type'] }}</td>
					<td>{{ $tc['ward'] }}</td>
					<td>{{ $tc['admission_date'] }}</td>
					<td>{{ $tc['currently_on_therapy'] }}</td>
					<td>{{ $tc['specimen_type'] }}</td>
					<td>{{ $tc['specimen_source'] }}</td>
					<td>{{ $tc['lab_id'] }}</td>
					<?php
						$isolateObtained = "";
						$isolateName = "";
						$drugTested = "";
						$zone = "";
						$sir = "";
						if (count($tc["isolates"]) > 0) {
							$isolateObtained .= "<p>Yes</p>";
							$tempIsolate = "";
							foreach ($tc["isolates"] as $suscept) {
								if(strcmp($tempIsolate, $suscept["isolate_name"]) != 0){
									$tempIsolate = $suscept["isolate_name"];
									$isolateName .= "<p>".$suscept["isolate_name"]."</p>";
								}else{
									$isolateName .= "<p>&nbsp;</p>";
								}
								$drugTested .= "<p>".$suscept["drug"]."</p>";
								$zone .= "<p>".$suscept["zone"]."</p>";
								$sir .= "<p>".$suscept["interpretation"]."</p>";
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
					<td>{{ $tc['test_type'] }}</td>
				</tr>
				@else
				<tr><td colspan="13">{{trans('messages.no-records-found')}}</td></tr>
				@endif
				@empty
				<tr><td colspan="13">{{trans('messages.no-records-found')}}</td></tr>
				@endforelse
			</tbody>
		</table>
	</div></div>
@endif
</div>

@stop