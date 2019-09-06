@extends("layout")
@section("content")
<div>
	<ol class="breadcrumb">
	  <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
	  <li class="active"><a href="{{ URL::route('reports.patient.index') }}">{{ Lang::choice('messages.report', 2) }}</a></li>
	  <li class="active">{{ trans('messages.patient-report') }}</li>
	</ol>
</div>
<div class='container-fluid'>
    {{ Form::open(array('url' => 'patientreport/'.$patient->id, 'class' => 'form-inline', 'id' => 'form-patientreport-filter', 'method'=>'POST')) }}
		{{ Form::hidden('patient', $patient->id, array('id' => 'patient')) }}
		<div class="row">
			<div class="col-sm-3">
				<label class="checkbox-inline">
	        		{{ Form::checkbox('pending', "1", isset($pending)) }}{{trans('messages.include-pending-tests')}}
				</label>
			</div>
			<div class="col-sm-3">
				<div class="row">
					<div class="col-sm-2">
						{{ Form::label('start', trans("messages.from")) }}</div><div class="col-sm-1">
			        	{{ Form::text('start', isset($input['start'])?$input['start']:null, 
			                array('class' => 'form-control standard-datepicker')) }}
			        </div>
		        </div>
	        </div>
	        <div class="col-sm-3">
				<div class="row">
			        <div class="col-sm-2">
				        {{ Form::label('end', trans("messages.to")) }}
				    </div>
				    <div class="col-sm-1">
		                {{ Form::text('end', isset($input['end'])?$input['end']:null, 
		                    array('class' => 'form-control standard-datepicker')) }}
		            </div>
	            </div>
            </div>
            <div class="col-sm-3">
				<div class="row">
		            <div class="col-sm-4">
			            {{ Form::button("<span class='glyphicon glyphicon-filter'></span> ".trans('messages.view'), 
			                    array('class' => 'btn btn-primary', 'id' => 'filter', 'type' => 'submit')) }}
		            </div>
			    </div>
		    </div>
	    </div>
	    {{ Form::hidden('visit_id', $visit, array('id'=>'visit_id')) }}
	{{ Form::close() }}
</div>
<br />
<div class="panel panel-primary" id="patientReport">
	<div class="panel-heading ">
		<span class="glyphicon glyphicon-user"></span>
		{{ trans('messages.patient-report') }}
        <div class="panel-btn">
        @if(count($verified) == count($tests))
		    {{ Form::open(array('url' => "patientreport/{$patient->id}/$visit/$testID", 'class' => 'form-inline', 'id' => 'form-patientreport-export', 'method'=>'POST')) }}
				{{ Form::hidden('patient', $patient->id, array('id' => 'patient')) }}
			    {{ Form::hidden('visit_id', $visit, array('id'=>'visit_id')) }}
			        {{ Form::submit(trans('messages.export'), array('class' => 'btn btn-success', 
			        	'id' => 'word', 'name' => 'word')) }}
			{{ Form::close() }}
	    @endif
	    </div>
	</div>
	<div class="panel-body">
		@if($error!='')
		<!-- if there are search errors, they will show here -->
			<div class="alert alert-info">{{ $error }}</div>
		@else

		<div id="report_content">
		@include("reportHeader")
		<strong>
			<p>
				{{trans('messages.patient-report').' - '.date('d-m-Y')}}
			</p>
		</strong>
		<table class="table table-bordered">
			<tbody>
				<tr>
					<td><strong>{{ trans('messages.patient-name')}}</strong></td>
					@if(Entrust::can('view_names'))
						<td>{{ $patient->name }}</td>
					@else
						<td>N/A</td>
					@endif
					<td><strong>{{ trans('messages.gender')}}</strong></td>
					<td>{{ $patient->getGender(false) }}</td>
				</tr>
				<tr>
					<td><strong>{{ trans('messages.patient-id')}}</strong></td>
					<td>{{ $patient->patient_number}}</td>
					<td><strong>{{ trans('messages.age')}}</strong></td>
					<td>{{ $patient->getAge()}}</td>
				</tr>
				<tr>
					<td><strong>{{ trans('messages.patient-lab-number')}}</strong></td>
					<td>{{ $patient->external_patient_number }}</td>
					<td><strong>{{ trans('messages.requesting-facility-department')}}</strong></td>
					<td>{{ Config::get('kblis.organization') }}</td>
				</tr>
			</tbody>
		</table>
		<table class="table table-bordered">
			<tbody>
				<tr>
					<td colspan="7"><strong>{{trans('messages.specimen')}}</strong></td>
				</tr>
				<tr>
					<td><strong>{{ Lang::choice('messages.specimen-type', 1)}}</strong></td>
					<td><strong>{{ Lang::choice('messages.test', 2)}}</strong></td>
					<td><strong>{{ trans('messages.date-ordered') }}</strong></td>
					<td><strong>{{ Lang::choice('messages.test-category', 2)}}</strong></td>
					<td><strong>{{ trans('messages.specimen-status')}}</strong></td>
					<td><strong>{{ trans('messages.collected-by')."/".trans('messages.rejected-by')}}</strong></td>
					<td><strong>{{ trans('messages.date-checked')}}</strong></td>
				</tr>
				@forelse($tests as $test)
						<tr>
							<td>{{ $test->specimen->specimenType->name }}</td>
							<td>{{ $test->testType->name }}</td>
							<td>{{ $test->isExternal()?$test->external()->request_date:$test->time_created }}</td>
							<td>{{ $test->testType->testCategory->name }}</td>
							@if($test->specimen->specimen_status_id == Specimen::NOT_COLLECTED)
								<td>{{trans('messages.specimen-not-collected')}}</td>
								<td> &nbsp;</td>
								<td>&nbsp;</td>
							@elseif($test->specimen->specimen_status_id == Specimen::ACCEPTED)
								<td>{{trans('messages.specimen-accepted')}}</td>
								<td>{{$test->specimen->acceptedBy->name}}</td>
								<td>{{$test->specimen->time_accepted}}</td>
							@elseif($test->specimen->specimen_status_id == Specimen::REJECTED)
								<td>{{trans('messages.specimen-rejected')}}</td>
								<td>{{$test->specimen->rejectedBy->name}}</td>
								<td>{{$test->specimen->time_rejected}}</td>
							@endif
						</tr>
				@empty
					<tr>
						<td colspan="7">{{trans("messages.no-records-found")}}</td>
					</tr>
				@endforelse

			</tbody>
		</table>
		<table class="table table-bordered">
			<tbody>
				<tr>
					<td colspan="8"><strong>{{trans('messages.test-results')}}</strong></td>
				</tr>
				<tr>
					<td><strong>{{Lang::choice('messages.test-type', 1)}}</strong></td>
					<td><strong>{{trans('messages.test-results-values')}}</strong></td>
					<td><strong>{{trans('messages.test-remarks')}}</strong></td>
					<td><strong>{{trans('messages.tested-by')}}</strong></td>
					<td><strong>{{trans('messages.results-entry-date')}}</strong></td>
					<td><strong>{{trans('messages.date-tested')}}</strong></td>
					<td><strong>{{trans('messages.verified-by')}}</strong></td>
					<td><strong>{{trans('messages.date-verified')}}</strong></td>
				</tr>
				@forelse($tests as $test)
						<tr>
							<td>{{ $test->testType->name }}</td>
							<td>
								@foreach($test->testResults as $result)
									<p>
										{{ Measure::find($result->measure_id)->name }}: {{ $result->result }}
										{{ Measure::getRange($test->visit->patient, $result->measure_id) }}
										{{ Measure::find($result->measure_id)->unit }}
									</p>
								@endforeach</td>
							<td>{{ $test->interpretation == '' ? 'N/A' : $test->interpretation }}</td>
							<td>{{ $test->tested_by > 0 ? $test->testedBy->name : trans('messages.pending')}}</td>
							<td>{{ $test->testResults->last()->time_entered }}</td>
							<td>{{ $test->time_completed }}</td>
							<td>{{ $test->verified_by > 0 ? $test->verifiedBy->name : trans('messages.verification-pending') }}</td>
							<td>{{ $test->time_verified }}</td>
						</tr>
				@empty
					<tr>
						<td colspan="8">{{trans("messages.no-records-found")}}</td>
					</tr>
				@endforelse
			</tbody>
		</table></div>
		@endif
		</div>
	</div>

</div>
@stop